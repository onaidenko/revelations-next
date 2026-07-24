import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';

function read(pathname) {
  return fs.readFileSync(new URL(`../${pathname}`, import.meta.url), 'utf8');
}

test('central brand assets preserve the REVELATIONS style contract', () => {
  const site = read('lib/site.js');
  assert.match(site, /BRAND_NAME = SITE_NAME/);
  assert.match(site, /Born as a podcast\. Built as a media platform\./);
  assert.match(site, /Future-Facing Media from Dubai/);
  assert.match(site, /\$\{BRAND_NAME\} - \$\{BRAND_DESCRIPTOR\}/);
});

test('owned identity and metadata templates use uppercase brand names and ASCII hyphens', () => {
  const paths = [
    'lib/site.js',
    'app/layout.jsx',
    'app/about/page.jsx',
    'app/contact/page.jsx',
    'app/advertise/page.jsx',
    'components/site-header.jsx',
    'components/site-footer.jsx',
    'components/section-page.jsx',
    'lib/sections.js',
  ];
  for (const pathname of paths) {
    const source = read(pathname);
    assert.doesNotMatch(source, /[—–]/, pathname);
    assert.doesNotMatch(source, /\bRevelations\b/, pathname);
  }
  assert.match(read('app/layout.jsx'), /template: '%s - REVELATIONS'/);
  assert.match(read('components/section-page.jsx'), /\$\{BRAND_NAME\} Podcast/);
  for (const pathname of [
    'app/access/page.jsx',
    'app/advertise/page.jsx',
    'app/contact/page.jsx',
  ]) {
    assert.doesNotMatch(read(pathname), /title: '.*REVELATIONS'/, pathname);
  }
});

test('AI generation separates owned brand typography from exact source evidence', () => {
  const generation = read('wordpress-cms/mu-plugins/revelations-editorial-ai-generate.php');
  assert.match(generation, /write the publication name exactly as REVELATIONS/);
  assert.match(generation, /never en or em dashes/);
  assert.match(generation, /Do not alter source evidence, direct quotations or verbatim fragments/);
});
