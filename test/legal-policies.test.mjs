import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(path, import.meta.url), 'utf8');

test('public legal pages describe the implemented analytics and consent behavior', async () => {
  const [privacy, cookies] = await Promise.all([
    read('../app/privacy-policy/page.jsx'),
    read('../app/cookie-policy/page.jsx'),
  ]);

  for (const source of [privacy, cookies]) {
    assert.match(source, /buildPageMetadata/);
    assert.match(source, /siteUrl: SITE_URL/);
    assert.match(source, /Basic Consent Mode v2/);
    assert.match(source, /Google Analytics 4/);
    assert.match(source, /Cookie settings/);
    assert.doesNotMatch(source, /ad_storage.*granted/);
  }

  assert.match(privacy, /\[legal entity name to be confirmed\]/);
  assert.match(privacy, /\[registered address to be confirmed\]/);
  assert.match(privacy, /\[privacy contact email to be confirmed\]/);
  assert.match(cookies, /revelations\.analytics-consent/);
  assert.match(cookies, /revelations-theme/);
  assert.match(cookies, /youtube-nocookie/);
});

test('footer and consent banner link visitors to both public policies', async () => {
  const [footer, consent, sitemap] = await Promise.all([
    read('../components/site-footer.jsx'),
    read('../components/analytics-consent.jsx'),
    read('../app/sitemap.js'),
  ]);

  for (const source of [footer, sitemap]) {
    assert.match(source, /privacy-policy/);
    assert.match(source, /cookie-policy/);
  }

  assert.match(consent, /href="\/cookie-policy"/);
});
