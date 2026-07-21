#!/usr/bin/env bash
set -euo pipefail

SITE_URL="${1:-https://revelations.me}"
LOWER_URL="$SITE_URL/about"
LEGACY_URL="$SITE_URL/About"
EXPECTED_FINAL="$SITE_URL/about"

LOWER_STATUS="$(
  curl -sS \
    --connect-timeout 5 \
    --max-time 20 \
    -o /dev/null \
    -w '%{http_code}' \
    "$LOWER_URL"
)"

if [[ "$LOWER_STATUS" != "200" ]]; then
  echo "ERROR lowercase_about_status=$LOWER_STATUS" >&2
  exit 1
fi

HEADERS="$(mktemp)"
trap 'rm -f "$HEADERS"' EXIT

LEGACY_STATUS="$(
  curl -sS \
    --connect-timeout 5 \
    --max-time 20 \
    -D "$HEADERS" \
    -o /dev/null \
    -w '%{http_code}' \
    "$LEGACY_URL"
)"

LEGACY_LOCATION="$(
  tr -d '\r' < "$HEADERS" |
    sed -n \
      's/^[Ll][Oo][Cc][Aa][Tt][Ii][Oo][Nn]:[[:space:]]*//p' |
    tail -n 1
)"

if [[ "$LEGACY_STATUS" != "301" ]]; then
  echo "ERROR legacy_about_status=$LEGACY_STATUS" >&2
  exit 1
fi

if [[ "$LEGACY_LOCATION" != "$EXPECTED_FINAL" ]]; then
  echo "ERROR legacy_about_location=$LEGACY_LOCATION" >&2
  exit 1
fi

CHAIN="$(
  curl -sS -L \
    --max-redirs 3 \
    --connect-timeout 5 \
    --max-time 20 \
    -o /dev/null \
    -w '%{http_code}|%{url_effective}|%{num_redirects}' \
    "$LEGACY_URL"
)"

IFS='|' read -r \
  FINAL_STATUS \
  FINAL_URL \
  REDIRECT_COUNT \
  <<< "$CHAIN"

if [[ "$FINAL_STATUS" != "200" ]]; then
  echo "ERROR final_status=$FINAL_STATUS" >&2
  exit 1
fi

if [[ "$FINAL_URL" != "$EXPECTED_FINAL" ]]; then
  echo "ERROR final_url=$FINAL_URL" >&2
  exit 1
fi

if [[ "$REDIRECT_COUNT" != "1" ]]; then
  echo "ERROR redirect_count=$REDIRECT_COUNT" >&2
  exit 1
fi

echo "lowercase_about_status=$LOWER_STATUS"
echo "legacy_about_status=$LEGACY_STATUS"
echo "legacy_about_location=$LEGACY_LOCATION"
echo "final_status=$FINAL_STATUS"
echo "final_url=$FINAL_URL"
echo "redirect_count=$REDIRECT_COUNT"
echo "production_legacy_paths=passed"
