#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BRANCH="admin-editorial"
SITE_URL="https://revelations.me"
CMS_API_URL="https://cms.revelations.me/wp-json/revelations/v1"
VERIFY_SOURCE="$ROOT/scripts/verify-production-release.py"
EXPECTED_COMMIT=""

usage() {
  cat <<'EOF'
Usage:
  bash scripts/prepare-production-release.sh \
    --expected-commit <full-sha>

Builds a commit-bound, immutable local production release under
.release/production/<full-sha>/ without contacting production.
EOF
}

while [[ "$#" -gt 0 ]]; do
  case "$1" in
    --expected-commit)
      EXPECTED_COMMIT="${2:-}"
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

RELEASE_BASE="$ROOT/.release/production"
RELEASE_DIR="$RELEASE_BASE/$EXPECTED_COMMIT"
STAGE_DIR="$RELEASE_BASE/.${EXPECTED_COMMIT}.prepare-$$"
PACKAGE_DIR="$STAGE_DIR/release"
ARCHIVE="$STAGE_DIR/revelations-production.tar.gz"
MANIFEST="$STAGE_DIR/manifest.json"
TAXONOMY_AUDIT_DIR="$STAGE_DIR/taxonomy-audit"

cleanup() {
  status=$?
  trap - EXIT
  rm -rf "$STAGE_DIR"
  exit "$status"
}
trap cleanup EXIT

cd "$ROOT"

echo "===== PREPARE PRODUCTION RELEASE PREFLIGHT ====="
echo "pwd=$PWD"
echo "branch=$(git branch --show-current)"

test "$(git branch --show-current)" = "$BRANCH"
test -z "$(git status --porcelain=v1 --untracked-files=all)"
test -f "$VERIFY_SOURCE"

git fetch origin "$BRANCH"

test "$(git rev-parse HEAD)" = "$EXPECTED_COMMIT"
test "$(git rev-parse "origin/$BRANCH")" = "$EXPECTED_COMMIT"
test ! -e "$RELEASE_DIR"
mkdir -p "$RELEASE_BASE"
mkdir -m 700 "$STAGE_DIR"

echo "expected_commit=$EXPECTED_COMMIT"
echo "working_tree=clean"
echo "ahead_behind=0 0"

echo
echo "===== LOCAL VALIDATION ====="
npm test
npm run lint
npm run audit:taxonomy -- --output-dir "$TAXONOMY_AUDIT_DIR"

python3 - "$TAXONOMY_AUDIT_DIR/seo-2c-taxonomy-audit.json" <<'PY'
import json
import sys
from pathlib import Path

summary = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))["summary"]
assert summary["critical"] == 0
assert summary["drift_total"] == 0
print("taxonomy_governance=passed")
PY

python3 "$VERIFY_SOURCE" self-test
bash -n scripts/prepare-production-release.sh
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

mkdir -p "$PACKAGE_DIR"
cp -a .next/standalone/. "$PACKAGE_DIR/"
mkdir -p "$PACKAGE_DIR/.next"
cp -a .next/static "$PACKAGE_DIR/.next/static"
cp .next/BUILD_ID "$PACKAGE_DIR/.next/BUILD_ID"

if [[ -d public ]]; then
  cp -a public "$PACKAGE_DIR/public"
fi

printf '%s\n' "$EXPECTED_COMMIT" > "$PACKAGE_DIR/RELEASE_COMMIT"

find "$PACKAGE_DIR" -type f \
  \( -name '.env' -o -name '.env.*' -o -name '*.pem' -o -name '*.key' \) \
  -delete

if find "$PACKAGE_DIR" -type f \
  \( -name '.env' -o -name '.env.*' -o -name '*.pem' -o -name '*.key' \) \
  -print -quit | grep -q .
then
  echo "Artifact contains environment or key files." >&2
  exit 1
fi

if grep -R -a -F -q 'https://staging.revelations.me' "$PACKAGE_DIR"; then
  echo "Artifact contains the staging domain." >&2
  exit 1
fi

COPYFILE_DISABLE=1 tar --no-xattrs -C "$PACKAGE_DIR" -czf "$ARCHIVE" .
ARCHIVE_SHA="$(shasum -a 256 "$ARCHIVE" | awk '{print $1}')"

test -z "$(git status --porcelain=v1 --untracked-files=all)"

python3 - "$MANIFEST" "$EXPECTED_COMMIT" "$BRANCH" "$BUILD_ID" \
  "$(basename "$ARCHIVE")" "$ARCHIVE_SHA" "$SITE_URL" "$CMS_API_URL" <<'PY'
import json
import sys
from datetime import datetime, timezone
from pathlib import Path

(
    path, commit, branch, build_id, archive, archive_sha, site_url, cms_api_url
) = sys.argv[1:]
manifest = {
    "schema_version": 1,
    "expected_commit": commit,
    "branch": branch,
    "build_id": build_id,
    "archive": archive,
    "archive_sha256": archive_sha,
    "created_at": datetime.now(timezone.utc).replace(microsecond=0).isoformat(),
    "site_url": site_url,
    "cms_api_url": cms_api_url,
    "checks": {
        "npm_test": "passed",
        "lint": "passed",
        "taxonomy_governance": "passed",
        "verifier_self_test": "passed",
        "standalone": "passed",
        "runtime_secret_files": "absent",
        "staging_domain": "absent",
    },
}
Path(path).write_text(json.dumps(manifest, indent=2, sort_keys=True) + "\n", encoding="utf-8")
PY

mv "$STAGE_DIR" "$RELEASE_DIR"
trap - EXIT

echo "PREPARED_RELEASE=$RELEASE_DIR"
echo "PREPARED_MANIFEST=$RELEASE_DIR/manifest.json"
echo "build_id=$BUILD_ID"
echo "artifact_sha=$ARCHIVE_SHA"
echo "artifact_runtime_env=absent"
echo "artifact_secret_files=none"
echo "artifact_staging_domain=none"
echo "PREPARE_SUCCESS"
