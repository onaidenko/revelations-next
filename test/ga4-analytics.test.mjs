import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

test('the public root layout delegates GA4 to the consent boundary', async () => {
  const layout = await readFile(
    new URL('../app/layout.jsx', import.meta.url),
    'utf8'
  );

  assert.match(layout, /import AnalyticsConsent from '@\/components\/analytics-consent';/);
  assert.match(layout, /<AnalyticsConsent \/>/);
  assert.equal(
    (layout.match(/<AnalyticsConsent \/>/g) ?? []).length,
    1,
    'the root-level boundary must not remount a second GA4 tag on route changes'
  );
  assert.doesNotMatch(layout, /googletagmanager/);
});
