import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

test('production build pins production configuration, restores the local env and rejects staging artifacts', async () => {
  const source = await readFile(
    new URL(
      '../scripts/build-production.sh',
      import.meta.url
    ),
    'utf8'
  );

  for (const required of [
    'set -euo pipefail',
    "PRODUCTION_SITE_URL='https://revelations.me'",
    "PRODUCTION_CMS_API_URL='https://cms.revelations.me/wp-json/revelations/v1'",
    "PRODUCTION_GA4_MEASUREMENT_ID='G-GZSZRHS50G'",
    'export NEXT_PUBLIC_SITE_URL',
    'export REVELATIONS_CMS_API_URL',
    'export NEXT_PUBLIC_GA4_MEASUREMENT_ID',
    'mktemp',
    'restore_env',
    'trap restore_env EXIT',
    'npm run build',
    'artifact_contains',
    'https://staging.revelations.me',
    'Production artifact does not contain the required GA4 Measurement ID.',
    'Standalone production environment has the wrong GA4 Measurement ID.',
    '.next/standalone/.env.production',
  ]) {
    assert.ok(
      source.includes(required),
      `missing production build safeguard: ${required}`
    );
  }

  assert.match(source, /! -d \.next/);
  assert.match(source, /command -v rg/);
  assert.match(source, /grep -R -a -F -q/);
  assert.doesNotMatch(source, /rm -rf ['"]?\.env\.production/);
});
