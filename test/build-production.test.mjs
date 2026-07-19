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
    'export NEXT_PUBLIC_SITE_URL',
    'export REVELATIONS_CMS_API_URL',
    'mktemp',
    'restore_env',
    'trap restore_env EXIT',
    'npm run build',
    'artifact_contains',
    'https://staging.revelations.me',
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
