import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

test('production build script pins canonical production configuration and rejects staging artifacts', async () => {
  const source = await readFile(
    new URL('../scripts/build-production.sh', import.meta.url),
    'utf8'
  );

  assert.match(source, /set -euo pipefail/);
  assert.match(source, /NEXT_PUBLIC_SITE_URL='https:\/\/revelations\.me'/);
  assert.match(source, /REVELATIONS_CMS_API_URL='https:\/\/cms\.revelations\.me\/wp-json\/revelations\/v1'/);
  assert.match(source, /npm run build/);
  assert.match(source, /! -d \.next/);
  assert.match(source, /https:\/\/staging\.revelations\.me/);
  assert.match(source, /https:\/\/revelations\.me/);
});
