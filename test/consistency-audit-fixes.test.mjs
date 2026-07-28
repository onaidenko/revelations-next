import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(path, import.meta.url), 'utf8');

test('legacy Access route redirects directly to its lowercase canonical URL', async () => {
  const proxy = await read('../proxy.js');

  assert.match(
    proxy,
    /request\.nextUrl\.pathname === '\/Access'/
  );
  assert.match(
    proxy,
    /url\.pathname = '\/access'/
  );
});

test('section and author templates emit their dedicated breadcrumbs', async () => {
  const sectionPage = await read('../components/section-page.jsx');
  const authorPage = await read('../app/authors/[slug]/page.jsx');

  assert.match(sectionPage, /buildSectionBreadcrumbJsonLd/);
  assert.match(authorPage, /buildAuthorBreadcrumbJsonLd/);
  assert.match(authorPage, /title: author\.name/);
  assert.doesNotMatch(authorPage, /title: `\$\{author\.name\} - REVELATIONS`/);
});

test('legacy Base44 article IDs have unique canonical article mappings', async () => {
  const articles = JSON.parse(await read('../data/articles.json'));
  const ids = articles.map((article) => String(article.id));
  const slugs = articles.map((article) => article.slug);
  const proxy = await read('../proxy.js');

  assert.equal(new Set(ids).size, ids.length);
  assert.ok(slugs.every(Boolean));
  assert.match(proxy, /LEGACY_ARTICLE_ROUTES/);
  assert.match(proxy, /\^\\d\+\$/);
  assert.match(proxy, /\^\[a-f0-9\]\{24\}/);
});
