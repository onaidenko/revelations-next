import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');

test('homepage keeps one identity surface and canonical author resolution', () => {
  const home = read('../app/page.jsx');

  assert.match(home, /formatArticleAuthors\(hero\)/);
  assert.match(home, /formatArticleAuthors\(article\)/);
  assert.doesNotMatch(home, /BRAND_TAGLINE/);
  assert.doesNotMatch(home, /BRAND_DESCRIPTOR/);
});

test('article uses one broad editorial reading grid', () => {
  const page = read('../app/[slug]/page.jsx');

  assert.match(page, /max-w-\[88rem\]/);
  assert.match(page, /<header className="mx-auto max-w-5xl px-6 md:px-10">/);
  assert.doesNotMatch(page, /<header className="mx-auto max-w-4xl/);
  assert.match(page, /mx-auto max-w-5xl px-6 py-14/);
  assert.doesNotMatch(page, /mx-auto max-w-2xl/);
  assert.match(page, /max-w-5xl px-6 md:mt-20/);
  assert.doesNotMatch(page, /<h1[^>]*max-w-/);
  assert.doesNotMatch(page, /article\.excerpt[^\n]*max-w-/);
  assert.match(page, /border-l-2 border-rose\/60/);
  assert.doesNotMatch(page, /border-y border-rose/);
  assert.match(page, /text-2xl leading-snug[^"\n]*md:text-3xl lg:text-4xl/);
});

test('Copy Link shares the desktop metadata row and remains an accessible text action', () => {
  const page = read('../app/[slug]/page.jsx');
  const share = read('../components/share-button.jsx');

  assert.match(page, /md:flex-row md:items-center md:justify-between/);
  assert.match(page, /<ShareButton url=\{canonical\} \/>/);
  assert.match(share, /navigator\.clipboard\?\.writeText/);
  assert.match(share, /focus-visible:ring-1 focus-visible:ring-rose/);
  assert.doesNotMatch(share, /border border-current/);
});

test('article taxonomy is public editorial metadata with primary topic first', () => {
  const taxonomy = read('../components/article-taxonomy.jsx');

  assert.match(taxonomy, /label="Topics"/);
  assert.match(taxonomy, /\.\.\.\(value\.primaryTopic \? \[value\.primaryTopic\] : \[\]\)/);
  assert.match(taxonomy, /findIndex\(\(item\) => item\.slug === term\.slug\)/);
  assert.doesNotMatch(taxonomy, /Primary topic/);
  assert.doesNotMatch(taxonomy, /More topics/);
  assert.doesNotMatch(taxonomy, /border border-rose/);
});

test('author module remains conditional for thin authors', () => {
  const page = read('../app/[slug]/page.jsx');

  assert.match(page, /publicAuthors\.length > 0/);
  assert.match(page, /author\.image\?\.url && <img/);
  assert.doesNotMatch(page, /rounded-full border border-border\/40/);
});
