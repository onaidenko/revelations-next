#!/usr/bin/env bash

set -euo pipefail

export NEXT_PUBLIC_SITE_URL='https://revelations.me'
export REVELATIONS_CMS_API_URL='https://cms.revelations.me/wp-json/revelations/v1'

npm run build

if [[ ! -d .next ]]; then
  echo 'Production build did not create .next.' >&2
  exit 1
fi

if rg --text --fixed-strings --quiet 'https://staging.revelations.me' .next; then
  echo 'Refusing production artifact containing the staging domain.' >&2
  exit 1
fi

if ! rg --text --fixed-strings --quiet 'https://revelations.me' .next; then
  echo 'Production artifact does not contain the production domain.' >&2
  exit 1
fi
