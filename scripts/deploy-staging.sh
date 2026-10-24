#!/usr/bin/env bash
# Local staging release launcher. Runs only a previously prepared, exact-SHA artifact.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REMOTE='revelations-prod'
SITE_URL='https://staging.revelations.me'
CMS_API_URL='https://staging.revelations.me/cms/wp-json/revelations/v1'
EXPECTED_COMMIT=''
RELEASE_BRANCH=''
PREPARED_RELEASE=''
SSH_OPTIONS=(-o BatchMode=yes -o ConnectTimeout=15 -o ServerAliveInterval=15 -o ServerAliveCountMax=4 -o LogLevel=ERROR)

usage() {
  echo 'Usage: npm run deploy:staging -- --expected-commit <full-sha> --release-branch <allowlisted-branch> --prepared-release <dir>'
}
while (( $# )); do
  case "$1" in
    --expected-commit) [[ $# -ge 2 ]] || { usage >&2; exit 2; }; EXPECTED_COMMIT="$2"; shift 2 ;;
    --release-branch) [[ $# -ge 2 ]] || { usage >&2; exit 2; }; RELEASE_BRANCH="$2"; shift 2 ;;
    --prepared-release) [[ $# -ge 2 ]] || { usage >&2; exit 2; }; PREPARED_RELEASE="$2"; shift 2 ;;
    --help|-h) usage; exit 0 ;;
    *) usage >&2; exit 2 ;;
  esac
done
[[ "$EXPECTED_COMMIT" =~ ^[0-9a-f]{40}$ ]] || exit 2
case "$RELEASE_BRANCH" in admin-editorial|release/ga4-consent-legal) ;; *) echo 'Release branch is not allowlisted' >&2; exit 2;; esac
[[ -n "$PREPARED_RELEASE" ]] || exit 2
cd "$ROOT"
git fetch origin "$RELEASE_BRANCH"
[[ "$(git rev-parse "origin/$RELEASE_BRANCH")" == "$EXPECTED_COMMIT" ]] || { echo 'Release branch has advanced or SHA mismatch' >&2; exit 1; }
PREPARED_RELEASE="$(cd "$PREPARED_RELEASE" && pwd)"
MANIFEST="$PREPARED_RELEASE/manifest.json"
[[ -f "$MANIFEST" ]] || exit 1
# Python validates type, contract, and exact source before upload.
MANIFEST_VALUES="$(python3 - "$MANIFEST" "$EXPECTED_COMMIT" "$RELEASE_BRANCH" "$SITE_URL" "$CMS_API_URL" <<'PY'
import json, re, sys
path, expected, branch, site, cms = sys.argv[1:]
d = json.load(open(path, encoding='utf8'))
assert d.get('schema_version') == 1
assert d.get('expected_commit') == expected and d.get('commit') == expected
assert d.get('source_branch') == branch
assert d.get('site_url') == site and d.get('cms_api_url') == cms
assert d.get('archive') == 'revelations-staging.tar.gz'
assert re.fullmatch(r'[A-Za-z0-9_-]+', d.get('build_id',''))
assert re.fullmatch(r'[0-9a-f]{64}', d.get('archive_sha256',''))
assert d.get('sha256') == d['archive_sha256']
checks = d.get('checks', {})
assert checks.get('production_ga4') == 'absent'
assert checks.get('runtime_secret_files') == 'absent'
assert checks.get('standalone') == 'passed'
print(d['build_id']); print(d['archive_sha256'])
PY
)"
BUILD_ID="$(printf '%s\n' "$MANIFEST_VALUES" | sed -n '1p')"
SHA="$(printf '%s\n' "$MANIFEST_VALUES" | sed -n '2p')"
[[ -n "$BUILD_ID" && "$SHA" =~ ^[0-9a-f]{64}$ ]] || exit 1
ARCHIVE="$PREPARED_RELEASE/revelations-staging.tar.gz"
[[ -f "$ARCHIVE" ]]
[[ "$(shasum -a 256 "$ARCHIVE" | awk '{print $1}')" == "$SHA" ]] || { echo 'Staging artifact hash mismatch' >&2; exit 1; }
# Validate the archive in isolation before ANY SSH call.
CHECK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/revelations-staging-check.XXXXXX")"
REMOTE_TMP=''
cleanup() {
  status=$?
  trap - EXIT
  rm -rf "$CHECK_DIR"
  if [[ -n "$REMOTE_TMP" ]]; then
    ssh "${SSH_OPTIONS[@]}" "$REMOTE" "rm -rf '$REMOTE_TMP'" >/dev/null 2>&1 || true
  fi
  exit "$status"
}
trap cleanup EXIT
# Trusted commit-bound artifact. Extraction occurs only into an isolated temp directory.
tar -xzf "$ARCHIVE" -C "$CHECK_DIR"
[[ -f "$CHECK_DIR/server.js" && -f "$CHECK_DIR/.next/BUILD_ID" && -f "$CHECK_DIR/RELEASE_COMMIT" ]]
[[ "$(cat "$CHECK_DIR/.next/BUILD_ID")" == "$BUILD_ID" ]]
[[ "$(cat "$CHECK_DIR/RELEASE_COMMIT")" == "$EXPECTED_COMMIT" ]]
[[ -d "$CHECK_DIR/.next/static" && -d "$CHECK_DIR/public" ]]
if find "$CHECK_DIR" -type f \( -name '.env' -o -name '.env.*' -o -name '*.pem' -o -name '*.key' \) -print -quit | grep -q .; then
  echo 'Artifact contains environment/secrets files' >&2; exit 1
fi
if grep -R -a -F -q 'G-GZSZRHS50G' "$CHECK_DIR"; then
  echo 'Artifact contains production GA4 ID' >&2; exit 1
fi
[[ -f "$ROOT/scripts/staging-remote-deploy.sh" && -f "$ROOT/scripts/staging-switch-transaction.sh" ]]
echo "STAGING_ARTIFACT_VALIDATED commit=$EXPECTED_COMMIT build_id=$BUILD_ID sha256=$SHA"

STAMP="$(date -u +%Y%m%d-%H%M%S)-$$"
REMOTE_TMP="/var/tmp/revelations-staging-upload-$STAMP"
ssh "${SSH_OPTIONS[@]}" "$REMOTE" "install -d -m 700 '$REMOTE_TMP'"
scp "${SSH_OPTIONS[@]}" "$ARCHIVE" "$REMOTE:$REMOTE_TMP/artifact.tgz"
scp "${SSH_OPTIONS[@]}" "$ROOT/scripts/staging-remote-deploy.sh" "$ROOT/scripts/staging-switch-transaction.sh" "$REMOTE:$REMOTE_TMP/"
ssh "${SSH_OPTIONS[@]}" "$REMOTE" "test \"\$(sha256sum '$REMOTE_TMP/artifact.tgz' | awk '{print \$1}')\" = '$SHA'"
echo 'STAGING_UPLOAD_VERIFIED'
# Avoid pipes here: SSH failure exit status cannot be masked by tee.
ssh "${SSH_OPTIONS[@]}" "$REMOTE" "bash '$REMOTE_TMP/staging-remote-deploy.sh' '$REMOTE_TMP' '$EXPECTED_COMMIT' '$BUILD_ID' '$SHA'"
echo "STAGING_DEPLOY_SCRIPT_SUCCESS commit=$EXPECTED_COMMIT build_id=$BUILD_ID"
