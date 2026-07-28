#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REMOTE=revelations-prod
SERVICE=revelations-staging.service
LIVE=/var/www/revelations-staging
COMMIT="${1:-}"
[[ "$COMMIT" =~ ^[0-9a-f]{40}$ ]] || { echo 'Full commit SHA required.' >&2; exit 2; }
RELEASE="${REVELATIONS_STAGING_RELEASE_DIR:-$ROOT/.release/staging/$COMMIT}"
MANIFEST="$RELEASE/manifest.json"
ARCHIVE="$RELEASE/revelations-staging.tar.gz"
test -f "$MANIFEST"; test -f "$ARCHIVE"
MANIFEST_VALUES="$(python3 - "$MANIFEST" "$COMMIT" <<'PY'
import json, sys
d=json.load(open(sys.argv[1])); assert d['commit']==sys.argv[2]
print(d['build_id']); print(d['sha256'])
PY
)"
BUILD_ID="$(printf '%s\n' "$MANIFEST_VALUES" | sed -n '1p')"
SHA="$(printf '%s\n' "$MANIFEST_VALUES" | sed -n '2p')"
test "$(shasum -a 256 "$ARCHIVE" | awk '{print $1}')" = "$SHA"
CHECK="$(mktemp -d /var/tmp/revelations-staging-check.XXXXXX)"
trap 'rm -rf "$CHECK"' EXIT
tar -xzf "$ARCHIVE" -C "$CHECK"
test -f "$CHECK/server.js"; test -f "$CHECK/.next/BUILD_ID"
test "$(cat "$CHECK/.next/BUILD_ID")" = "$BUILD_ID"
test -n "$(find "$CHECK/.next/static" -type f -print -quit)"
test -d "$CHECK/public"; test "$(cat "$CHECK/RELEASE_COMMIT")" = "$COMMIT"

STAMP="$(date -u +%Y%m%d-%H%M%S)"
REMOTE_TMP="/var/tmp/revelations-staging-deploy-$STAMP-$$"
ssh "$REMOTE" "install -d -m 700 '$REMOTE_TMP'"
scp "$ARCHIVE" "$REMOTE:$REMOTE_TMP/artifact.tgz"
ssh "$REMOTE" "test \"\$(sha256sum '$REMOTE_TMP/artifact.tgz' | awk '{print \$1}')\" = '$SHA'"
ssh "$REMOTE" "bash -s -- '$REMOTE_TMP' '$LIVE' '$SERVICE' '$COMMIT' '$BUILD_ID'" <<'REMOTE'
set -euo pipefail
tmp="$1"; live="$2"; service="$3"; commit="$4"; build="$5"
candidate="$tmp/candidate"; next="${live}.next-$(date -u +%Y%m%d-%H%M%S)"; backup="${live}.backup-$(date -u +%Y%m%d-%H%M%S)"
mkdir "$candidate"; tar -xzf "$tmp/artifact.tgz" -C "$candidate"
test "$(cat "$candidate/RELEASE_COMMIT")" = "$commit"; test "$(cat "$candidate/.next/BUILD_ID")" = "$build"
test -n "$(find "$candidate/.next/static" -type f -print -quit)"; test -d "$candidate/public"
NEXT_PUBLIC_SITE_URL=https://staging.revelations.me REVELATIONS_CMS_API_URL=https://staging.revelations.me/cms/wp-json/revelations/v1 PORT=3103 HOSTNAME=127.0.0.1 node "$candidate/server.js" >"$tmp/candidate.log" 2>&1 & pid=$!
trap 'kill "$pid" 2>/dev/null || true' EXIT
for i in $(seq 1 20); do curl -fsS --max-time 3 http://127.0.0.1:3103/ >"$tmp/home.html" 2>/dev/null && break; sleep 1; done
grep -o -E '(href|src)="/_next/static/[^"]+"' "$tmp/home.html" | sed -E 's/^[^"]+"//; s/"$//' | sort -u >"$tmp/assets"
test -s "$tmp/assets"; while IFS= read -r asset; do test "$(curl -sS -o /dev/null -w '%{http_code}' "http://127.0.0.1:3103$asset")" = 200; done <"$tmp/assets"
kill "$pid"; wait "$pid" 2>/dev/null || true; trap - EXIT
test -f "$live/.env.production"; cp -a "$live/.env.production" "$candidate/.env.production"
chown -R deploy:deploy "$candidate"; mv "$candidate" "$next"; mv "$live" "$backup"; mv "$next" "$live"; systemctl restart "$service"
for i in $(seq 1 20); do systemctl is-active --quiet "$service" && curl -fsS --max-time 3 http://127.0.0.1:3001/ >/dev/null && break; sleep 1; done
test "$(cat "$live/RELEASE_COMMIT")" = "$commit"; test "$(cat "$live/.next/BUILD_ID")" = "$build"
REMOTE
printf 'STAGING_DEPLOY_SUCCESS commit=%s build_id=%s sha256=%s\n' "$COMMIT" "$BUILD_ID" "$SHA"
