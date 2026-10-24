#!/usr/bin/env bash
set -euo pipefail

ROOT="${REVELATIONS_RELEASE_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
SITE_URL=https://staging.revelations.me
CMS_API_URL=https://staging.revelations.me/cms/wp-json/revelations/v1
PRODUCTION_GA4_MEASUREMENT_ID='G-GZSZRHS50G'
EXPECTED_COMMIT=''
RELEASE_BRANCH=''

usage() { echo 'Usage: prepare-staging-release.sh --expected-commit <sha> --release-branch <branch>'; }
while [[ "$#" -gt 0 ]]; do
  case "$1" in
    --expected-commit) EXPECTED_COMMIT="${2:-}"; shift 2 ;;
    --release-branch) RELEASE_BRANCH="${2:-}"; shift 2 ;;
    --help|-h) usage; exit 0 ;;
    *) usage >&2; exit 2 ;;
  esac
done

[[ "$EXPECTED_COMMIT" =~ ^[0-9a-f]{40}$ ]] || { echo 'Full commit SHA required.' >&2; exit 2; }
case "$RELEASE_BRANCH" in admin-editorial|release/ga4-consent-legal) ;; *) echo 'Release branch is not allowlisted.' >&2; exit 2;; esac
cd "$ROOT"
git fetch origin "$RELEASE_BRANCH"
test "$(git rev-parse "origin/$RELEASE_BRANCH")" = "$EXPECTED_COMMIT"

RELEASE_DIR="$ROOT/.release/staging/$EXPECTED_COMMIT"
STAGE_DIR="$ROOT/.release/staging/.${EXPECTED_COMMIT}.prepare-$$"
SOURCE_DIR="$(mktemp -d "${TMPDIR:-/tmp}/revelations-staging-source.XXXXXX")"
PACKAGE_DIR="$STAGE_DIR/release"
ARCHIVE="$STAGE_DIR/revelations-staging.tar.gz"
mkdir -p "$ROOT/.release/staging"
test ! -e "$RELEASE_DIR"
mkdir -m 700 "$STAGE_DIR"
cleanup() { status=$?; trap - EXIT; git -C "$ROOT" worktree remove --force "$SOURCE_DIR" >/dev/null 2>&1 || true; rm -rf "$STAGE_DIR"; exit "$status"; }
trap cleanup EXIT
git worktree add --detach "$SOURCE_DIR" "$EXPECTED_COMMIT" >/dev/null
test "$(git -C "$SOURCE_DIR" rev-parse HEAD)" = "$EXPECTED_COMMIT"
test -z "$(git -C "$SOURCE_DIR" status --porcelain=v1 --untracked-files=all)"
cd "$SOURCE_DIR"

if [[ ! -d node_modules/he ]]; then
  npm ci
fi
npm test
npm run lint
rm -rf .next
NEXT_PUBLIC_SITE_URL="$SITE_URL" REVELATIONS_CMS_API_URL="$CMS_API_URL" NEXT_PUBLIC_GA4_MEASUREMENT_ID='' npm run build

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

if rg --text --fixed-strings --quiet "$PRODUCTION_GA4_MEASUREMENT_ID" "$PACKAGE_DIR"; then
  echo 'Staging artifact contains the production GA4 Measurement ID.' >&2
  exit 1
fi

COPYFILE_DISABLE=1 tar --no-xattrs -C "$PACKAGE_DIR" -czf "$ARCHIVE" .
SHA="$(shasum -a 256 "$ARCHIVE" | awk '{print $1}')"
BUILD_ID="$(cat .next/BUILD_ID)"
python3 - "$STAGE_DIR/manifest.json" "$EXPECTED_COMMIT" "$RELEASE_BRANCH" "$BUILD_ID" "$SHA" "$SITE_URL" "$CMS_API_URL" <<'PY'
import json, sys
from pathlib import Path
p,c,b,i,s,u,api=sys.argv[1:]
Path(p).write_text(json.dumps({"schema_version":1,"commit":c,"expected_commit":c,"source_branch":b,"build_id":i,"sha256":s,"archive_sha256":s,"archive":"revelations-staging.tar.gz","site_url":u,"cms_api_url":api,"checks":{"production_ga4":"absent","runtime_secret_files":"absent","standalone":"passed"}}, indent=2) + "\n")
PY
mv "$STAGE_DIR" "$RELEASE_DIR"
trap - EXIT
git -C "$ROOT" worktree remove --force "$SOURCE_DIR"
printf 'PREPARED_STAGING_RELEASE=%s\nBUILD_ID=%s\nSHA256=%s\n' "$RELEASE_DIR" "$BUILD_ID" "$SHA"
