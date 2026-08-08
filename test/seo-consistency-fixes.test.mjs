import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';

function read(pathname) {
  return fs.readFileSync(new URL(`../${pathname}`, import.meta.url), 'utf8');
}

test('core metadata uses the approved Podcast and About titles', () => {
  const podcast = read('app/podcast/page.jsx');
  const about = read('app/about/page.jsx');

  assert.match(podcast, /const PODCAST_TITLE = 'Podcast - REVELATIONS'/);
  assert.match(podcast, /title:\s*\{\s*absolute: PODCAST_TITLE/);
  assert.match(about, /const ABOUT_TITLE = 'About REVELATIONS'/);
  assert.match(about, /title: \{ absolute: ABOUT_TITLE \}/);
});

test('public root schema has no legacy REVELATIONS Media alias', () => {
  const site = read('lib/site.js');
  const layout = read('app/layout.jsx');

  assert.doesNotMatch(site, /REVELATIONS Media/);
  assert.doesNotMatch(layout, /alternateNames/);
});

test('Header keeps primary editorial links distinct from utility controls', () => {
  const header = read('components/site-header.jsx');

  assert.match(header, /aria-label="Primary editorial"/);
  assert.match(header, /<nav[\s\S]*?NAV_LINKS\.map/);
  assert.match(header, /<div className="flex items-center gap-10">[\s\S]*?href="\/access"[\s\S]*?<ThemeToggle/);
});

test('Podcast featured episode has one semantic H2 implementation', () => {
  const sectionPage = read('components/section-page.jsx');
  const featuredStart = sectionPage.indexOf('{isPodcast && featured && (');
  const featuredEnd = sectionPage.indexOf('{(sticky || feed.length > 0)', featuredStart);
  const featuredMarkup = sectionPage.slice(featuredStart, featuredEnd);
  const coverMarkup = featuredMarkup.slice(
    0,
    featuredMarkup.indexOf(') : (')
  );

  assert.equal((coverMarkup.match(/<h2/g) || []).length, 1);
  assert.equal((coverMarkup.match(/\{featured\.title\}/g) || []).length, 1);
  assert.match(coverMarkup, /<h2[^>]*>\s*\{featured\.title\}/);
});

test('production build starts from a clean Next output', () => {
  const build = read('scripts/build-production.sh');

  assert.match(build, /rm -rf \.next\n\nnpm run build/);
  assert.match(build, /artifact_contains\(\) \{/);
});
