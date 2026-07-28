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

test('legacy article route has unique mappings and fails closed for unknown values', async () => {
  const articles = JSON.parse(await read('../data/articles.json'));
  const ids = articles.map((article) => String(article.id));
  const slugs = articles.map((article) => article.slug);
  const route = await read('../app/article/[legacy]/route.js');
  const config = await read('../next.config.mjs');

  assert.equal(new Set(ids).size, ids.length);
  assert.ok(slugs.every(Boolean));
  assert.match(route, /legacyIdToSlug/);
  assert.match(route, /canonicalSlugs/);
  assert.match(route, /export async function GET/);
  assert.match(route, /export async function HEAD/);
  assert.match(route, /new NextResponse\(null, \{ status: 404 \}\)/);
  assert.doesNotMatch(config, /source: '\/article\/:slug'/);
});
