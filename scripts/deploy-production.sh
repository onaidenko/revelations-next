#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BRANCH="admin-editorial"
REMOTE="revelations-prod"
SERVICE="revelations-production.service"
LIVE_ROOT="/var/www/revelations-production"
SITE_URL="https://revelations.me"
CMS_API_URL="https://cms.revelations.me/wp-json/revelations/v1"
VERIFY_SOURCE="$ROOT/scripts/verify-production-release.py"

EXPECTED_COMMIT=""
CONFIRM=""

usage() {
  cat <<'EOF'
Usage:
  bash scripts/deploy-production.sh \
    --expected-commit <full-sha> \
    --confirm deploy-production-<first-12-sha>

The command deploys only origin/admin-editorial at the exact expected commit.
EOF
}

while [[ "$#" -gt 0 ]]; do
  case "$1" in
    --expected-commit)
      EXPECTED_COMMIT="${2:-}"
      shift 2
      ;;
    --confirm)
      CONFIRM="${2:-}"
      shift 2
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      echo "Unknown argument: $1" >&2
      usage >&2
      exit 2
      ;;
  esac
done

if [[ ! "$EXPECTED_COMMIT" =~ ^[0-9a-f]{40}$ ]]; then
  echo "A full 40-character commit SHA is required." >&2
  exit 2
fi

EXPECTED_CONFIRM="deploy-production-${EXPECTED_COMMIT:0:12}"
if [[ "$CONFIRM" != "$EXPECTED_CONFIRM" ]]; then
  echo "Confirmation token must be: $EXPECTED_CONFIRM" >&2
  exit 2
fi

STAMP="$(date -u +%Y%m%d-%H%M%S)"
LOCAL_TMP="$(mktemp -d "${TMPDIR:-/tmp}/revelations-production-deploy.XXXXXX")"
PACKAGE_DIR="$LOCAL_TMP/release"
ARCHIVE="$LOCAL_TMP/revelations-production-$STAMP.tar.gz"
REMOTE_TMP="/tmp/revelations-production-deploy-$STAMP-$$"
REMOTE_LOG="$LOCAL_TMP/remote-deploy.log"
TAXONOMY_AUDIT_DIR="$LOCAL_TMP/taxonomy-audit"

cleanup() {
  status=$?
  trap - EXIT
  ssh "$REMOTE" "rm -rf '$REMOTE_TMP'" >/dev/null 2>&1 || true
  rm -rf "$LOCAL_TMP"
  exit "$status"
}
trap cleanup EXIT

cd "$ROOT"

echo "===== PRODUCTION DEPLOY PREFLIGHT ====="
echo "pwd=$PWD"
echo "branch=$(git branch --show-current)"

test "$(git branch --show-current)" = "$BRANCH"
test -z "$(git status --porcelain=v1 --untracked-files=all)"
test -f "$VERIFY_SOURCE"

git fetch origin "$BRANCH"

test "$(git rev-parse HEAD)" = "$EXPECTED_COMMIT"
test "$(git rev-parse "origin/$BRANCH")" = "$EXPECTED_COMMIT"

echo "expected_commit=$EXPECTED_COMMIT"
echo "working_tree=clean"
echo "ahead_behind=0 0"

echo
echo "===== LOCAL VALIDATION ====="
npm test
npm run lint
npm run audit:taxonomy -- \
  --output-dir "$TAXONOMY_AUDIT_DIR"

python3 - "$TAXONOMY_AUDIT_DIR/seo-2c-taxonomy-audit.json" <<'PY'
import json
import sys
from pathlib import Path

summary = json.loads(
    Path(sys.argv[1]).read_text(encoding="utf-8")
)["summary"]

assert summary["critical"] == 0
assert summary["drift_total"] == 0

print("taxonomy_governance=passed")
PY

python3 "$VERIFY_SOURCE" self-test
bash -n scripts/deploy-production.sh

echo
echo "===== BUILD AND PACKAGE ====="
npm run build:production

test -d .next/standalone
test -d .next/static
test -f .next/BUILD_ID
test -f .next/standalone/server.js

BUILD_ID="$(cat .next/BUILD_ID)"
test -n "$BUILD_ID"

rm -rf "$PACKAGE_DIR"
mkdir -p "$PACKAGE_DIR"
cp -a .next/standalone/. "$PACKAGE_DIR/"
mkdir -p "$PACKAGE_DIR/.next"
rm -rf "$PACKAGE_DIR/.next/static"
cp -a .next/static "$PACKAGE_DIR/.next/static"
cp .next/BUILD_ID "$PACKAGE_DIR/.next/BUILD_ID"

if [[ -d public ]]; then
  rm -rf "$PACKAGE_DIR/public"
  cp -a public "$PACKAGE_DIR/public"
fi

printf '%s\n' "$EXPECTED_COMMIT" > "$PACKAGE_DIR/RELEASE_COMMIT"

find "$PACKAGE_DIR" \
  -type f \
  \( \
    -name '.env' \
    -o -name '.env.*' \
    -o -name '*.pem' \
    -o -name '*.key' \
  \) \
  -delete

if find "$PACKAGE_DIR" \
  -type f \
  \( \
    -name '.env' \
    -o -name '.env.*' \
    -o -name '*.pem' \
    -o -name '*.key' \
  \) \
  -print -quit | grep -q .
then
  echo "Artifact contains environment or key files." >&2
  exit 1
fi

if grep -R -a -F -q \
  'https://staging.revelations.me' \
  "$PACKAGE_DIR"
then
  echo "Artifact contains the staging domain." >&2
  exit 1
fi

COPYFILE_DISABLE=1 tar \
  --no-xattrs \
  -C "$PACKAGE_DIR" \
  -czf "$ARCHIVE" \
  .

ARCHIVE_SHA="$(
  shasum -a 256 "$ARCHIVE" | awk '{print $1}'
)"

test -z "$(git status --porcelain=v1 --untracked-files=all)"

echo "build_id=$BUILD_ID"
echo "artifact_sha=$ARCHIVE_SHA"
echo "artifact_runtime_env=absent"
echo "artifact_secret_files=none"
echo "artifact_staging_domain=none"

echo
echo "===== UPLOAD ISOLATED RELEASE ====="
ssh "$REMOTE" "mkdir -m 700 -p '$REMOTE_TMP'"
scp \
  "$ARCHIVE" \
  "$VERIFY_SOURCE" \
  "$REMOTE:$REMOTE_TMP/"

echo
echo "===== REMOTE CANDIDATE AND ATOMIC SWITCH ====="

set +e
ssh "$REMOTE" "bash -s -- \
  '$REMOTE_TMP' \
  '$(basename "$ARCHIVE")' \
  '$ARCHIVE_SHA' \
  '$(basename "$VERIFY_SOURCE")' \
  '$EXPECTED_COMMIT' \
  '$BUILD_ID' \
  '$SERVICE' \
  '$LIVE_ROOT' \
  '$SITE_URL' \
  '$CMS_API_URL'" >"$REMOTE_LOG" 2>&1 <<'REMOTE_SCRIPT'
set -euo pipefail

REMOTE_TMP="$1"
ARCHIVE_NAME="$2"
EXPECTED_ARCHIVE_SHA="$3"
VERIFY_NAME="$4"
EXPECTED_COMMIT="$5"
EXPECTED_BUILD_ID="$6"
SERVICE="$7"
LIVE_ROOT="$8"
SITE_URL="$9"
CMS_API_URL="${10}"

ARCHIVE="$REMOTE_TMP/$ARCHIVE_NAME"
VERIFY="$REMOTE_TMP/$VERIFY_NAME"
STAMP="$(date -u +%Y%m%d-%H%M%S)"
BACKUP="/root/revelations-production-before-$STAMP"
CANDIDATE="/var/www/revelations-production.candidate-$STAMP"
PREVIOUS="/var/www/revelations-production.previous-$STAMP"
FAILED_RELEASE="$BACKUP/failed-release"
PREDEPLOY_MANIFEST="$BACKUP/predeploy-manifest.json"
CANDIDATE_MANIFEST="$BACKUP/candidate-manifest.json"
CANDIDATE_LOG="$BACKUP/candidate.log"
RUNTIME_ENV_SOURCE="$LIVE_ROOT/.env.production"
CANDIDATE_PID=""
SWITCHED=0
SUCCESS=0

fail() {
  echo "DEPLOY_ERROR=$*" >&2
  exit 1
}

stop_candidate() {
  set +e
  if [[ -n "$CANDIDATE_PID" ]]; then
    kill -- "-$CANDIDATE_PID" >/dev/null 2>&1 || true
    kill "$CANDIDATE_PID" >/dev/null 2>&1 || true
    wait "$CANDIDATE_PID" >/dev/null 2>&1 || true
    CANDIDATE_PID=""
  fi
}

verify_with_retries() {
  local base="$1"
  local manifest="$2"
  local attempts="${3:-18}"
  local index

  for index in $(seq 1 "$attempts"); do
    if python3 "$VERIFY" verify \
      --base "$base" \
      --manifest "$manifest"
    then
      return 0
    fi
    sleep 5
  done

  return 1
}

rollback() {
  set +e
  stop_candidate

  if [[ "$SWITCHED" -eq 1 ]]; then
    systemctl stop "$SERVICE" >/dev/null 2>&1 || true

    if [[ -d "$LIVE_ROOT" ]]; then
      mv "$LIVE_ROOT" "$FAILED_RELEASE" || true
    fi

    if [[ -d "$PREVIOUS" ]]; then
      mv "$PREVIOUS" "$LIVE_ROOT" || true
    elif [[ -d "$BACKUP/live-release" ]]; then
      cp -a "$BACKUP/live-release" "$LIVE_ROOT" || true
    fi

    systemctl start "$SERVICE" >/dev/null 2>&1 || true

    verify_with_retries \
      "$SITE_URL" \
      "$PREDEPLOY_MANIFEST" \
      18 \
      >"$BACKUP/rollback-verification.log" 2>&1 \
      || true
  elif [[ -d "$CANDIDATE" ]]; then
    mv "$CANDIDATE" "$BACKUP/failed-candidate" || true
  fi
}

on_exit() {
  status=$?
  trap - EXIT

  if [[ "$status" -ne 0 && "$SUCCESS" -ne 1 ]]; then
    rollback
  fi

  exit "$status"
}
trap on_exit EXIT

test "$(id -u)" -eq 0
test -f "$ARCHIVE"
test -f "$VERIFY"
test -d "$LIVE_ROOT"
test -f "$LIVE_ROOT/server.js"
test -f "$LIVE_ROOT/.next/BUILD_ID"
test -f "$RUNTIME_ENV_SOURCE"

if [[ "$(
  sha256sum "$ARCHIVE" | cut -d ' ' -f1
)" != "$EXPECTED_ARCHIVE_SHA" ]]; then
  fail "archive_sha_mismatch"
fi

if ! grep -Fqx \
  "NEXT_PUBLIC_SITE_URL=$SITE_URL" \
  "$RUNTIME_ENV_SOURCE"
then
  fail "live_runtime_site_url_missing"
fi

if ! grep -Fqx \
  "REVELATIONS_CMS_API_URL=$CMS_API_URL" \
  "$RUNTIME_ENV_SOURCE"
then
  fail "live_runtime_cms_url_missing"
fi

RUNTIME_SECRET_PRESENT="no"
if grep -q \
  '^REVELATIONS_REVALIDATION_SECRET=.' \
  "$RUNTIME_ENV_SOURCE"
then
  RUNTIME_SECRET_PRESENT="yes"
fi

systemctl is-active --quiet "$SERVICE" \
  || fail "service_not_active"

nginx -t >/dev/null 2>&1 \
  || fail "nginx_invalid_before_deploy"

OWNER="$(stat -c '%U' "$LIVE_ROOT")"
GROUP="$(stat -c '%G' "$LIVE_ROOT")"
MODE="$(stat -c '%a' "$LIVE_ROOT")"
ENV_MODE="$(stat -c '%a' "$RUNTIME_ENV_SOURCE")"

mkdir -m 700 -p "$BACKUP"
cp -a "$LIVE_ROOT" "$BACKUP/live-release"
systemctl cat "$SERVICE" > "$BACKUP/service-unit.txt"
nginx -T > "$BACKUP/nginx-before.txt" 2>&1

if ! python3 "$VERIFY" snapshot \
  --base "$SITE_URL" \
  --origin "$SITE_URL" \
  --output "$PREDEPLOY_MANIFEST"
then
  fail "predeploy_public_snapshot_failed"
fi

mkdir -p "$CANDIDATE"
tar -xzf "$ARCHIVE" -C "$CANDIDATE"

test -f "$CANDIDATE/server.js" \
  || fail "candidate_server_missing"
test -f "$CANDIDATE/.next/BUILD_ID" \
  || fail "candidate_build_id_missing"
test -f "$CANDIDATE/RELEASE_COMMIT" \
  || fail "candidate_commit_marker_missing"

if [[ "$(
  cat "$CANDIDATE/.next/BUILD_ID"
)" != "$EXPECTED_BUILD_ID" ]]; then
  fail "candidate_build_id_mismatch"
fi

if [[ "$(
  cat "$CANDIDATE/RELEASE_COMMIT"
)" != "$EXPECTED_COMMIT" ]]; then
  fail "candidate_commit_mismatch"
fi

if find "$CANDIDATE" \
  -type f \
  \( \
    -name '.env' \
    -o -name '.env.*' \
    -o -name '*.pem' \
    -o -name '*.key' \
  \) \
  -print -quit | grep -q .
then
  fail "candidate_artifact_contains_runtime_files"
fi

cat > "$CANDIDATE/.env.production" <<EOF
NEXT_PUBLIC_SITE_URL=$SITE_URL
REVELATIONS_CMS_API_URL=$CMS_API_URL
EOF

chown -R "$OWNER:$GROUP" "$CANDIDATE"
chmod "$MODE" "$CANDIDATE"
chmod "$ENV_MODE" "$CANDIDATE/.env.production"

CANDIDATE_PORT="$(
  python3 - <<'PY'
import socket

with socket.socket() as sock:
    sock.bind(("127.0.0.1", 0))
    print(sock.getsockname()[1])
PY
)"

SERVICE_USER="$(
  systemctl show -p User --value "$SERVICE"
)"
[[ -n "$SERVICE_USER" ]] || SERVICE_USER="root"

if [[ "$SERVICE_USER" = "root" ]]; then
  setsid bash -c \
    "cd '$CANDIDATE' && exec env PORT='$CANDIDATE_PORT' HOSTNAME=127.0.0.1 NODE_ENV=production node server.js" \
    >"$CANDIDATE_LOG" 2>&1 &
else
  setsid runuser -u "$SERVICE_USER" -- bash -c \
    "cd '$CANDIDATE' && exec env PORT='$CANDIDATE_PORT' HOSTNAME=127.0.0.1 NODE_ENV=production node server.js" \
    >"$CANDIDATE_LOG" 2>&1 &
fi
CANDIDATE_PID=$!

CANDIDATE_READY=0
for _ in $(seq 1 40); do
  if curl -fsS \
    --connect-timeout 2 \
    --max-time 10 \
    "http://127.0.0.1:$CANDIDATE_PORT/" \
    >/dev/null
  then
    CANDIDATE_READY=1
    break
  fi
  sleep 1
done

if [[ "$CANDIDATE_READY" -ne 1 ]]; then
  fail "candidate_not_ready"
fi

if ! curl -fsS \
  --connect-timeout 3 \
  --max-time 15 \
  "$CMS_API_URL/health" \
  >/dev/null
then
  fail "candidate_cms_health_failed"
fi

if ! python3 "$VERIFY" snapshot \
  --base "http://127.0.0.1:$CANDIDATE_PORT" \
  --origin "$SITE_URL" \
  --output "$CANDIDATE_MANIFEST"
then
  fail "candidate_snapshot_failed"
fi

stop_candidate

rm -f "$CANDIDATE/.env.production"
cp -a "$RUNTIME_ENV_SOURCE" "$CANDIDATE/.env.production"

if ! cmp -s \
  "$RUNTIME_ENV_SOURCE" \
  "$CANDIDATE/.env.production"
then
  fail "runtime_environment_transfer_failed"
fi

nginx -t >/dev/null 2>&1 \
  || fail "nginx_invalid_before_switch"

systemctl stop "$SERVICE" \
  || fail "service_stop_failed"

mv "$LIVE_ROOT" "$PREVIOUS" \
  || fail "live_release_move_failed"
mv "$CANDIDATE" "$LIVE_ROOT" \
  || fail "candidate_activation_failed"
SWITCHED=1

find_service_ports() {
  local main_pid="$1"
  local control_group="$2"

  python3 - "$main_pid" "$control_group" <<'PY'
from __future__ import annotations

import re
import subprocess
import sys
from pathlib import Path

main_pid = sys.argv[1].strip()
control_group = sys.argv[2].strip()
service_pids: set[int] = set()

if main_pid.isdigit() and int(main_pid) > 0:
    service_pids.add(int(main_pid))

if control_group.startswith("/"):
    cgroup_root = Path("/sys/fs/cgroup") / control_group.lstrip("/")
    if cgroup_root.is_dir():
        for process_file in cgroup_root.rglob("cgroup.procs"):
            try:
                for value in process_file.read_text(
                    encoding="utf-8"
                ).splitlines():
                    if value.isdigit():
                        service_pids.add(int(value))
            except OSError:
                continue

if not service_pids:
    raise SystemExit(1)

try:
    output = subprocess.check_output(
        ["ss", "-ltnpH"],
        text=True,
        stderr=subprocess.DEVNULL,
    )
except (OSError, subprocess.CalledProcessError):
    raise SystemExit(1)

ports: set[int] = set()
for line in output.splitlines():
    socket_pids = {
        int(value)
        for value in re.findall(r"pid=(\d+)", line)
    }
    if service_pids.isdisjoint(socket_pids):
        continue

    fields = line.split()
    if len(fields) < 4:
        continue

    local_address = fields[3]
    port_text = local_address.rsplit(":", 1)[-1]
    if port_text.isdigit():
        ports.add(int(port_text))

if not ports:
    raise SystemExit(1)

for port in sorted(ports):
    print(port)
PY
}

systemctl start "$SERVICE" \
  || fail "service_start_failed"

SERVICE_READY=0
LOOPBACK_PORT=""

for _ in $(seq 1 40); do
  if ! systemctl is-active --quiet "$SERVICE"; then
    sleep 1
    continue
  fi

  MAIN_PID="$(
    systemctl show -p MainPID --value "$SERVICE"
  )"
  CONTROL_GROUP="$(
    systemctl show -p ControlGroup --value "$SERVICE"
  )"
  SERVICE_PORTS="$(
    find_service_ports \
      "$MAIN_PID" \
      "$CONTROL_GROUP" \
      || true
  )"

  while IFS= read -r candidate_port; do
    [[ "$candidate_port" =~ ^[0-9]+$ ]] || continue

    if curl -fsS \
      --connect-timeout 3 \
      --max-time 15 \
      "http://127.0.0.1:$candidate_port/" \
      >/dev/null
    then
      LOOPBACK_PORT="$candidate_port"
      SERVICE_READY=1
      break
    fi
  done <<< "$SERVICE_PORTS"

  if [[ "$SERVICE_READY" -eq 1 ]]; then
    break
  fi

  sleep 1
done

if [[ "$SERVICE_READY" -ne 1 || -z "$LOOPBACK_PORT" ]]; then
  fail "service_loopback_not_ready_after_switch"
fi

nginx -t >/dev/null 2>&1 \
  || fail "nginx_invalid_after_switch"

if ! verify_with_retries \
  "$SITE_URL" \
  "$CANDIDATE_MANIFEST" \
  18
then
  fail "public_candidate_match_failed"
fi

if [[ "$(
  cat "$LIVE_ROOT/.next/BUILD_ID"
)" != "$EXPECTED_BUILD_ID" ]]; then
  fail "active_build_id_mismatch"
fi

if [[ "$(
  cat "$LIVE_ROOT/RELEASE_COMMIT"
)" != "$EXPECTED_COMMIT" ]]; then
  fail "active_commit_marker_mismatch"
fi

if ! cmp -s \
  "$PREVIOUS/.env.production" \
  "$LIVE_ROOT/.env.production"
then
  fail "active_runtime_environment_mismatch"
fi

CMS_HEALTH="$(
  curl -sS \
    --connect-timeout 3 \
    --max-time 15 \
    -o /dev/null \
    -w '%{http_code}' \
    "$CMS_API_URL/health"
)"
if [[ "$CMS_HEALTH" != "200" ]]; then
  fail "cms_health=$CMS_HEALTH"
fi

REVALIDATION_GET="$(
  curl -sS \
    --connect-timeout 3 \
    --max-time 15 \
    -o /dev/null \
    -w '%{http_code}' \
    "$SITE_URL/api/revalidate"
)"
if [[ "$REVALIDATION_GET" != "405" ]]; then
  fail "revalidation_get=$REVALIDATION_GET"
fi

ABOUT_LOWER_STATUS="$(
  curl -sS \
    --connect-timeout 3 \
    --max-time 15 \
    -o /dev/null \
    -w '%{http_code}' \
    "$SITE_URL/about"
)"

if [[ "$ABOUT_LOWER_STATUS" != "200" ]]; then
  fail "about_lower_status=$ABOUT_LOWER_STATUS"
fi

ABOUT_HEADERS="$BACKUP/about-legacy-headers.txt"

ABOUT_LEGACY_STATUS="$(
  curl -sS \
    --connect-timeout 3 \
    --max-time 15 \
    -D "$ABOUT_HEADERS" \
    -o /dev/null \
    -w '%{http_code}' \
    "$SITE_URL/About"
)"

ABOUT_LEGACY_LOCATION="$(
  tr -d '\r' < "$ABOUT_HEADERS" |
    sed -n \
      's/^[Ll][Oo][Cc][Aa][Tt][Ii][Oo][Nn]:[[:space:]]*//p' |
    tail -n 1
)"

if [[ "$ABOUT_LEGACY_STATUS" != "301" ]]; then
  fail "about_legacy_status=$ABOUT_LEGACY_STATUS"
fi

if [[ "$ABOUT_LEGACY_LOCATION" != "$SITE_URL/about" ]]; then
  fail "about_legacy_location=$ABOUT_LEGACY_LOCATION"
fi

ABOUT_CHAIN="$(
  curl -sS -L \
    --max-redirs 3 \
    --connect-timeout 3 \
    --max-time 20 \
    -o /dev/null \
    -w '%{http_code}|%{url_effective}|%{num_redirects}' \
    "$SITE_URL/About"
)"

IFS='|' read -r \
  ABOUT_FINAL_STATUS \
  ABOUT_FINAL_URL \
  ABOUT_REDIRECT_COUNT \
  <<< "$ABOUT_CHAIN"

if [[ "$ABOUT_FINAL_STATUS" != "200" ]]; then
  fail "about_final_status=$ABOUT_FINAL_STATUS"
fi

if [[ "$ABOUT_FINAL_URL" != "$SITE_URL/about" ]]; then
  fail "about_final_url=$ABOUT_FINAL_URL"
fi

if [[ "$ABOUT_REDIRECT_COUNT" != "1" ]]; then
  fail "about_redirect_count=$ABOUT_REDIRECT_COUNT"
fi

STAGING_HEADERS="$BACKUP/staging-headers.txt"
STAGING_STATUS="$(
  curl -sS \
    --connect-timeout 3 \
    --max-time 15 \
    -D "$STAGING_HEADERS" \
    -o /dev/null \
    -w '%{http_code}' \
    'https://staging.revelations.me/'
)"
if [[ "$STAGING_STATUS" != "200" ]]; then
  fail "staging_status=$STAGING_STATUS"
fi

if ! grep -qi \
  '^x-robots-tag:.*noindex' \
  "$STAGING_HEADERS"
then
  fail "staging_noindex_missing"
fi

SUCCESS=1

echo "DEPLOY_RESULT=success"
echo "FRONTEND_COMMIT=$EXPECTED_COMMIT"
echo "BUILD_ID=$EXPECTED_BUILD_ID"
echo "SERVICE=$SERVICE"
echo "LOOPBACK_PORT=$LOOPBACK_PORT"
echo "BACKUP_DIR=$BACKUP"
echo "PREVIOUS_RELEASE=$PREVIOUS"
echo "RUNTIME_ENV_TRANSFERRED=yes"
echo "RUNTIME_SECRET_PRESENT=$RUNTIME_SECRET_PRESENT"
echo "PUBLIC_MANIFEST_MATCH=yes"
echo "CMS_HEALTH=$CMS_HEALTH"
echo "REVALIDATION_GET=$REVALIDATION_GET"
echo "ABOUT_LOWER_STATUS=$ABOUT_LOWER_STATUS"
echo "ABOUT_LEGACY_STATUS=$ABOUT_LEGACY_STATUS"
echo "ABOUT_LEGACY_LOCATION=$ABOUT_LEGACY_LOCATION"
echo "ABOUT_FINAL_STATUS=$ABOUT_FINAL_STATUS"
echo "ABOUT_FINAL_URL=$ABOUT_FINAL_URL"
echo "ABOUT_REDIRECT_COUNT=$ABOUT_REDIRECT_COUNT"
echo "ABOUT_REDIRECT=passed"
echo "STAGING_STATUS=$STAGING_STATUS"
REMOTE_SCRIPT
REMOTE_STATUS=$?
set -e

cat "$REMOTE_LOG"

if [[ "$REMOTE_STATUS" -ne 0 ]]; then
  exit "$REMOTE_STATUS"
fi

grep -Fqx 'DEPLOY_RESULT=success' "$REMOTE_LOG"
grep -Fqx 'RUNTIME_ENV_TRANSFERRED=yes' "$REMOTE_LOG"
grep -Fqx 'PUBLIC_MANIFEST_MATCH=yes' "$REMOTE_LOG"
grep -Fqx 'ABOUT_REDIRECT=passed' "$REMOTE_LOG"

test -z "$(git status --porcelain=v1 --untracked-files=all)"
test "$(git rev-parse HEAD)" = "$EXPECTED_COMMIT"
test "$(git rev-parse "origin/$BRANCH")" = "$EXPECTED_COMMIT"

echo
echo "===== PRODUCTION DEPLOY COMPLETE ====="
sed -n \
  -e '/^FRONTEND_COMMIT=/p' \
  -e '/^BUILD_ID=/p' \
  -e '/^SERVICE=/p' \
  -e '/^LOOPBACK_PORT=/p' \
  -e '/^BACKUP_DIR=/p' \
  -e '/^PREVIOUS_RELEASE=/p' \
  -e '/^RUNTIME_ENV_TRANSFERRED=/p' \
  -e '/^RUNTIME_SECRET_PRESENT=/p' \
  -e '/^PUBLIC_MANIFEST_MATCH=/p' \
  -e '/^CMS_HEALTH=/p' \
  -e '/^REVALIDATION_GET=/p' \
  -e '/^ABOUT_LOWER_STATUS=/p' \
  -e '/^ABOUT_LEGACY_STATUS=/p' \
  -e '/^ABOUT_LEGACY_LOCATION=/p' \
  -e '/^ABOUT_FINAL_STATUS=/p' \
  -e '/^ABOUT_FINAL_URL=/p' \
  -e '/^ABOUT_REDIRECT_COUNT=/p' \
  -e '/^ABOUT_REDIRECT=/p' \
  -e '/^STAGING_STATUS=/p' \
  "$REMOTE_LOG"
echo "cms_writes=0"
echo "database_writes=0"
echo "signed_revalidation_post=not_performed"
echo "openai_requests=0"
git rev-list --left-right --count "origin/$BRANCH"...HEAD
