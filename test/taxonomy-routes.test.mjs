import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const routePaths = [
  '../app/topics/[slug]/page.jsx',
  '../app/series/[slug]/page.jsx',
  '../app/locations/[slug]/page.jsx',
  '../app/tags/[slug]/page.jsx',
];

test('all canonical taxonomy routes use shared SSR and 404 contracts', async () => {
  const shared = await readFile(
    new URL(
      '../lib/taxonomy-page.jsx',
      import.meta.url
    ),
    'utf8'
  );

  assert.match(shared, /notFound\(\)/);
  assert.match(
    shared,
    /buildTaxonomyCollectionJsonLd/
  );
  assert.match(
    shared,
    /buildTaxonomyBreadcrumbJsonLd/
  );

  for (const path of routePaths) {
    const source = await readFile(
      new URL(path, import.meta.url),
      'utf8'
    );

    assert.match(source, /generateStaticParams/);
    assert.match(source, /generateMetadata/);
    assert.match(source, /dynamicParams = true/);
    assert.match(source, /TaxonomyRoutePage/);
  }
});

test('article page exposes structured taxonomy without raw tag cloud links', async () => {
  const source = await readFile(
    new URL(
      '../app/[slug]/page.jsx',
      import.meta.url
    ),
    'utf8'
  );

  assert.match(source, /ArticleTaxonomy/);
  assert.match(
    source,
    /getArticleTaxonomyPresentation/
  );
  assert.doesNotMatch(
    source,
    /article\.tags\.map/
  );
});
