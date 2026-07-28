#!/usr/bin/env bash
set -euo pipefail

ROOT="${REVELATIONS_RELEASE_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
BRANCH=admin-editorial
SITE_URL=https://staging.revelations.me
CMS_API_URL=https://staging.revelations.me/cms/wp-json/revelations/v1
EXPECTED_COMMIT="${1:-}"

[[ "$EXPECTED_COMMIT" =~ ^[0-9a-f]{40}$ ]] || { echo 'Full commit SHA required.' >&2; exit 2; }
cd "$ROOT"
if [[ "${REVELATIONS_ALLOW_DETACHED_RELEASE:-0}" != 1 ]]; then
  test "$(git branch --show-current)" = "$BRANCH"
fi
test -z "$(git status --porcelain=v1 --untracked-files=all)"
git fetch origin "$BRANCH"
test "$(git rev-parse HEAD)" = "$EXPECTED_COMMIT"
if [[ "${REVELATIONS_ALLOW_DETACHED_RELEASE:-0}" = 1 ]]; then
  git merge-base --is-ancestor "$EXPECTED_COMMIT" "origin/$BRANCH"
else
  test "$(git rev-parse "origin/$BRANCH")" = "$EXPECTED_COMMIT"
fi

RELEASE_DIR="$ROOT/.release/staging/$EXPECTED_COMMIT"
STAGE_DIR="$ROOT/.release/staging/.${EXPECTED_COMMIT}.prepare-$$"
PACKAGE_DIR="$STAGE_DIR/release"
ARCHIVE="$STAGE_DIR/revelations-staging.tar.gz"
mkdir -p "$ROOT/.release/staging"
test ! -e "$RELEASE_DIR"
mkdir -m 700 "$STAGE_DIR"
trap 'rm -rf "$STAGE_DIR"' EXIT

npm test
npm run lint
rm -rf .next
NEXT_PUBLIC_SITE_URL="$SITE_URL" REVELATIONS_CMS_API_URL="$CMS_API_URL" npm run build

test -f .next/standalone/server.js
test -d .next/static
test -n "$(find .next/static -type f -print -quit)"
test -f .next/BUILD_ID
test -d public

mkdir -p "$PACKAGE_DIR/.next"
cp -a .next/standalone/. "$PACKAGE_DIR/"
cp -a .next/static "$PACKAGE_DIR/.next/static"
cp .next/BUILD_ID "$PACKAGE_DIR/.next/BUILD_ID"
cp -a public "$PACKAGE_DIR/public"
printf '%s\n' "$EXPECTED_COMMIT" > "$PACKAGE_DIR/RELEASE_COMMIT"
find "$PACKAGE_DIR" -type f \( -name '.env' -o -name '.env.*' -o -name '*.pem' -o -name '*.key' \) -delete
COPYFILE_DISABLE=1 tar --no-xattrs -C "$PACKAGE_DIR" -czf "$ARCHIVE" .
SHA="$(shasum -a 256 "$ARCHIVE" | awk '{print $1}')"
BUILD_ID="$(cat .next/BUILD_ID)"
python3 - "$STAGE_DIR/manifest.json" "$EXPECTED_COMMIT" "$BUILD_ID" "$SHA" <<'PY'
import json, sys
from pathlib import Path
Path(sys.argv[1]).write_text(json.dumps({"commit": sys.argv[2], "build_id": sys.argv[3], "sha256": sys.argv[4], "archive": "revelations-staging.tar.gz"}, indent=2) + "\n")
PY
mv "$STAGE_DIR" "$RELEASE_DIR"
trap - EXIT
printf 'PREPARED_STAGING_RELEASE=%s\nBUILD_ID=%s\nSHA256=%s\n' "$RELEASE_DIR" "$BUILD_ID" "$SHA"
