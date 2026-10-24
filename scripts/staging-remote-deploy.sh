#!/usr/bin/env bash
# On-host worker for an immutable staging artifact. NEVER use this for production.
set -euo pipefail

if (( $# != 4 )); then
  echo 'Usage: staging-remote-deploy.sh <temp-dir> <expected-sha> <build-id> <archive-sha256>' >&2
  exit 2
fi
REMOTE_TMP="$1"
EXPECTED_COMMIT="$2"
BUILD_ID="$3"
EXPECTED_SHA="$4"
SITE_URL='https://staging.revelations.me'
CMS_API_URL='https://staging.revelations.me/cms/wp-json/revelations/v1'
SERVICE='revelations-staging.service'
LIVE='/var/www/revelations-staging'
MODULE="$REMOTE_TMP/staging-switch-transaction.sh"
ARCHIVE="$REMOTE_TMP/artifact.tgz"
[[ "$EXPECTED_COMMIT" =~ ^[0-9a-f]{40}$ ]] || exit 2
[[ "$EXPECTED_SHA" =~ ^[0-9a-f]{64}$ ]] || exit 2
[[ "$BUILD_ID" =~ ^[a-zA-Z0-9_-]+$ ]] || exit 2
[[ -f "$ARCHIVE" && -f "$MODULE" ]] || exit 2

# Serialized across deployments, lock remains held until verification completes.
exec 9>/var/tmp/revelations-staging-deploy.lock
flock -n 9 || { echo 'STAGING_DEPLOY_LOCKED' >&2; exit 1; }

[[ "$(sha256sum "$ARCHIVE" | awk '{print $1}')" == "$EXPECTED_SHA" ]] || exit 1
systemctl is-active --quiet "$SERVICE"
nginx -t >/dev/null 2>&1
[[ -f "$LIVE/RELEASE_COMMIT" && -f "$LIVE/.next/BUILD_ID" && -f "$LIVE/.env.production" ]]
OLD_COMMIT="$(cat "$LIVE/RELEASE_COMMIT")"
OLD_BUILD_ID="$(cat "$LIVE/.next/BUILD_ID")"
[[ "$OLD_COMMIT" =~ ^[0-9a-f]{40}$ ]]
[[ -n "$OLD_BUILD_ID" ]]
# A staging runtime must never configure the production GA4 measurement ID.
! grep -Fq 'G-GZSZRHS50G' "$LIVE/.env.production"
grep -Fqx "NEXT_PUBLIC_SITE_URL=$SITE_URL" "$LIVE/.env.production"
grep -Fqx "REVELATIONS_CMS_API_URL=$CMS_API_URL" "$LIVE/.env.production"

STAMP="$(date -u +%Y%m%d-%H%M%S)-$$"
BACKUP="${LIVE}.backup-$STAMP"
CANDIDATE="${LIVE}.candidate-$STAMP"
PREVIOUS="${LIVE}.previous-$STAMP"
mkdir -m 700 "$BACKUP"
cp -a "$LIVE" "$BACKUP/live-release"
[[ "$(cat "$BACKUP/live-release/RELEASE_COMMIT")" == "$OLD_COMMIT" ]]
[[ "$(cat "$BACKUP/live-release/.next/BUILD_ID")" == "$OLD_BUILD_ID" ]]
systemctl cat "$SERVICE" > "$BACKUP/service-unit.txt"
echo "STAGING_BACKUP=$BACKUP"

# Source only after the backup exists; trap guards all further operations.
# shellcheck source=staging-switch-transaction.sh
source "$MODULE"

staging_check_public_noindex() {
  local headers="$1"
  curl -fsS --max-time 15 -D "$headers" -o /dev/null "$SITE_URL/" || return 1
  grep -Eiq '^x-robots-tag:.*noindex' "$headers"
}
# The public proxy points at the actual staging service listener, not the
# isolated candidate port. Check it before trying public HTTPS. The first few
# probes may fail normally just after systemctl start.
STAGING_SERVICE_LOOPBACK='http://127.0.0.1:3001/'
staging_verify_service_listener() {
  systemctl is-active --quiet "$SERVICE" || return 1
  curl -fsS --connect-timeout 1 --max-time 3 \
    -o /dev/null "$STAGING_SERVICE_LOOPBACK" 2>/dev/null
}
staging_verify_old_runtime() {
  staging_old_files_ok || return 1
  staging_verify_service_listener || return 1
  staging_check_public_noindex "$BACKUP/rollback-headers.txt"
}
staging_verify_new_runtime() {
  local p status location final
  staging_new_files_ok || return 1
  staging_verify_service_listener || return 1
  staging_check_public_noindex "$BACKUP/staging-headers.txt" || return 1
  for p in / /news /people /tech /privacy-policy /cookie-policy /sitemap.xml /robots.txt; do
    status="$(curl -sS --max-time 15 -o /dev/null -w '%{http_code}' "$SITE_URL$p")" || return 1
    [[ "$status" == 200 ]] || return 1
  done
  for p in /how-openxai-will-change-the-worlds-perceptions /article/how-openxai-will-change-the-worlds-perceptions; do
    location="$(curl -sS --max-time 15 -D "$BACKUP/redirect-headers.txt" -o /dev/null -w '%{http_code}' "$SITE_URL$p")" || return 1
    [[ "$location" == 308 ]] || return 1
    grep -Eiq '^location:.*ashton-hettiarachi-how-openxai-will-change-the-worlds-perceptions' "$BACKUP/redirect-headers.txt" || return 1
  done
  [[ "$(cat "$LIVE/RELEASE_COMMIT")" == "$EXPECTED_COMMIT" ]] || return 1
  [[ "$(cat "$LIVE/.next/BUILD_ID")" == "$BUILD_ID" ]] || return 1
}

staging_install_traps
mkdir "$CANDIDATE"
tar -xzf "$ARCHIVE" -C "$CANDIDATE"
[[ -f "$CANDIDATE/server.js" && -f "$CANDIDATE/.next/BUILD_ID" && -f "$CANDIDATE/RELEASE_COMMIT" ]]
[[ "$(cat "$CANDIDATE/.next/BUILD_ID")" == "$BUILD_ID" ]]
[[ "$(cat "$CANDIDATE/RELEASE_COMMIT")" == "$EXPECTED_COMMIT" ]]
[[ -d "$CANDIDATE/.next/static" && -d "$CANDIDATE/public" ]]
if find "$CANDIDATE" -type f \( -name '.env' -o -name '.env.*' -o -name '*.pem' -o -name '*.key' \) -print -quit | grep -q .; then
  echo 'ARTIFACT_HAS_SECRETS' >&2; exit 1
fi
if grep -R -a -F -q 'G-GZSZRHS50G' "$CANDIDATE"; then
  echo 'PRODUCTION_GA4_IN_STAGING' >&2; exit 1
fi

# Reuse only staging runtime values; never source production environment.
cp -a "$LIVE/.env.production" "$CANDIDATE/.env.production"
chown -R "$(stat -c '%U:%G' "$LIVE")" "$CANDIDATE"
chmod "$(stat -c '%a' "$LIVE")" "$CANDIDATE"

CANDIDATE_PORT="$(python3 - <<'PY'
import socket
with socket.socket() as sock:
    sock.bind(('127.0.0.1', 0))
    print(sock.getsockname()[1])
PY
)"
# Candidate runs as staging service user; production is never contacted by this worker.
SERVICE_USER="$(systemctl show -p User --value "$SERVICE")"
if [[ -z "$SERVICE_USER" || "$SERVICE_USER" == root ]]; then
  setsid bash -c 'cd "$1"; export PORT="$2" HOSTNAME=127.0.0.1 NODE_ENV=production; exec node server.js' bash "$CANDIDATE" "$CANDIDATE_PORT" > "$BACKUP/candidate.log" 2>&1 &
else
  setsid runuser -u "$SERVICE_USER" -- bash -c 'cd "$1"; export PORT="$2" HOSTNAME=127.0.0.1 NODE_ENV=production; exec node server.js' bash "$CANDIDATE" "$CANDIDATE_PORT" > "$BACKUP/candidate.log" 2>&1 &
fi
CANDIDATE_PID=$!
CANDIDATE_OK=0
for i in $(seq 1 30); do
  if curl -fsS --max-time 3 "http://127.0.0.1:$CANDIDATE_PORT/" -o "$BACKUP/candidate-home.html" 2>/dev/null; then
    CANDIDATE_OK=1; break
  fi
  sleep 1
done
[[ "$CANDIDATE_OK" == 1 ]] || { echo 'CANDIDATE_HEALTH_FAILED' >&2; exit 1; }
for p in /news /people /tech /privacy-policy /cookie-policy /sitemap.xml /robots.txt; do
  curl -fsS --max-time 12 "http://127.0.0.1:$CANDIDATE_PORT$p" -o /dev/null || { echo "CANDIDATE_ROUTE_FAILED=$p" >&2; exit 1; }
done
# Check actual Next.js chunk(s), not only HTML; assets are required for visual QA.
python3 - "$BACKUP/candidate-home.html" "$BACKUP/candidate-assets.txt" <<'PY'
import re,sys
html=open(sys.argv[1],encoding='utf8').read()
urls=sorted(set(re.findall(r'(?:href|src)="(/_next/static/[^"?]+)',html)))
if not urls: raise SystemExit('no Next.js static assets')
open(sys.argv[2],'w').write('\n'.join(urls[:25])+'\n')
PY
while IFS= read -r asset; do
  curl -fsS --max-time 12 "http://127.0.0.1:$CANDIDATE_PORT$asset" -o /dev/null || exit 1
done < "$BACKUP/candidate-assets.txt"

staging_switch_transaction
# Keep BOTH the timestamped full copy and displaced previous directory.
echo "STAGING_DEPLOY_SUCCESS commit=$EXPECTED_COMMIT build_id=$BUILD_ID previous=$PREVIOUS backup=$BACKUP"
