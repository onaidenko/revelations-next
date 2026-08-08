#!/usr/bin/env bash

set -euo pipefail

PRODUCTION_SITE_URL='https://revelations.me'
PRODUCTION_CMS_API_URL='https://cms.revelations.me/wp-json/revelations/v1'

export NEXT_PUBLIC_SITE_URL="$PRODUCTION_SITE_URL"
export REVELATIONS_CMS_API_URL="$PRODUCTION_CMS_API_URL"

ENV_FILE='.env.production'
ENV_BACKUP="$(mktemp "${TMPDIR:-/tmp}/revelations-env-production-backup.XXXXXX")"
ENV_REPLACEMENT="$(mktemp "${TMPDIR:-/tmp}/revelations-env-production-build.XXXXXX")"
ENV_EXISTED=0

if [[ -f "$ENV_FILE" ]]; then
  cp "$ENV_FILE" "$ENV_BACKUP"
  ENV_EXISTED=1
fi

restore_env() {
  if [[ "$ENV_EXISTED" -eq 1 ]]; then
    cp "$ENV_BACKUP" "$ENV_FILE"
  else
    rm -f "$ENV_FILE"
  fi

  rm -f "$ENV_BACKUP" "$ENV_REPLACEMENT"
}

trap restore_env EXIT

if [[ -f "$ENV_FILE" ]]; then
  awk \
    -v site="$PRODUCTION_SITE_URL" \
    -v cms="$PRODUCTION_CMS_API_URL" '
      BEGIN {
        site_seen = 0
        cms_seen = 0
      }
      /^NEXT_PUBLIC_SITE_URL=/ {
        print "NEXT_PUBLIC_SITE_URL=" site
        site_seen = 1
        next
      }
      /^REVELATIONS_CMS_API_URL=/ {
        print "REVELATIONS_CMS_API_URL=" cms
        cms_seen = 1
        next
      }
      {
        print
      }
      END {
        if (!site_seen) {
          print "NEXT_PUBLIC_SITE_URL=" site
        }
        if (!cms_seen) {
          print "REVELATIONS_CMS_API_URL=" cms
        }
      }
    ' "$ENV_FILE" > "$ENV_REPLACEMENT"
else
  printf '%s\n%s\n' \
    "NEXT_PUBLIC_SITE_URL=$PRODUCTION_SITE_URL" \
    "REVELATIONS_CMS_API_URL=$PRODUCTION_CMS_API_URL" \
    > "$ENV_REPLACEMENT"
fi

mv "$ENV_REPLACEMENT" "$ENV_FILE"

rm -rf .next

npm run build

if [[ ! -d .next ]]; then
  echo 'Production build did not create .next.' >&2
  exit 1
fi

artifact_contains() {
  local needle="$1"

  if command -v rg >/dev/null 2>&1; then
    rg --text --fixed-strings --quiet "$needle" .next
  else
    grep -R -a -F -q -- "$needle" .next
  fi
}

if artifact_contains 'https://staging.revelations.me'; then
  echo 'Refusing production artifact containing the staging domain.' >&2
  exit 1
fi

if ! artifact_contains "$PRODUCTION_SITE_URL"; then
  echo 'Production artifact does not contain the production domain.' >&2
  exit 1
fi

if [[ -f .next/standalone/.env.production ]]; then
  if ! grep -Fqx \
    "NEXT_PUBLIC_SITE_URL=$PRODUCTION_SITE_URL" \
    .next/standalone/.env.production
  then
    echo 'Standalone production environment has the wrong site URL.' >&2
    exit 1
  fi

  if ! grep -Fqx \
    "REVELATIONS_CMS_API_URL=$PRODUCTION_CMS_API_URL" \
    .next/standalone/.env.production
  then
    echo 'Standalone production environment has the wrong CMS URL.' >&2
    exit 1
  fi
fi
