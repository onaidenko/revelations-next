import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const footer = readFileSync(
  new URL('../components/site-footer.jsx', import.meta.url),
  'utf8'
);
const site = readFileSync(
  new URL('../lib/site.js', import.meta.url),
  'utf8'
);

test('footer keeps only canonical visible brand copy', () => {
  assert.match(footer, /\{BRAND_NAME\}/);
  assert.match(footer, /\{BRAND_TAGLINE\}/);
  assert.doesNotMatch(footer, /DEFAULT_DESCRIPTION/);
  assert.doesNotMatch(footer, /future-facing media publication/);
  assert.match(site, /Born as a podcast\. Built as a media platform\./);
});

test('footer social controls retain official URLs and accessible outline treatment', () => {
  for (const name of ['Instagram', 'X', 'YouTube']) {
    assert.match(site, new RegExp(`name: '${name}'`));
  }

  assert.match(footer, /SOCIAL_PROFILES\.map/);
  assert.match(footer, /target="_blank"/);
  assert.match(footer, /rel="noreferrer"/);
  assert.match(footer, /border border-border\/50/);
  assert.match(footer, /hover:border-rose\/70/);
  assert.match(footer, /focus-visible:ring-1 focus-visible:ring-rose/);
  assert.doesNotMatch(footer, /rounded-full/);
});
