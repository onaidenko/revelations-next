import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import {
  buildTaxonomyIndexBreadcrumbJsonLd,
  buildTaxonomyIndexCollectionJsonLd,
} from '../lib/seo.js';
import {
  getTaxonomyIndexConfig,
  TAXONOMY_INDEX_ORDER,
} from '../lib/taxonomy-indexes.js';

const componentPath = new URL(
  '../components/taxonomy-index-page.jsx',
  import.meta.url
);
const archivePath = new URL(
  '../app/archive/page.jsx',
  import.meta.url
);
const footerPath = new URL(
  '../components/site-footer.jsx',
  import.meta.url
);
const headerPath = new URL(
  '../components/site-header.jsx',
  import.meta.url
);
const sitemapPath = new URL(
  '../app/sitemap.js',
  import.meta.url
);
const revalidationPath = new URL(
  '../lib/revalidation-contract.js',
  import.meta.url
);

const ROUTES = {
  topics: new URL('../app/topics/page.jsx', import.meta.url),
  series: new URL('../app/series/page.jsx', import.meta.url),
  locations: new URL('../app/locations/page.jsx', import.meta.url),
  tags: new URL('../app/tags/page.jsx', import.meta.url),
};

test('taxonomy index registry exposes four unique public collections', () => {
  assert.deepEqual(
    TAXONOMY_INDEX_ORDER,
    ['topics', 'series', 'locations', 'tags']
  );

  const configs = TAXONOMY_INDEX_ORDER.map(
    getTaxonomyIndexConfig
  );

  assert.deepEqual(
    configs.map((config) => config.pathname),
    ['/topics', '/series', '/locations', '/tags']
  );
  assert.deepEqual(
    configs.map((config) => config.name),
    ['Topics', 'Series', 'Locations', 'Entities']
  );
  assert.equal(
    new Set(
      configs.map((config) => config.description)
    ).size,
    4
  );
});

test('taxonomy index schemas expose canonical collection and ItemList data', () => {
  const siteUrl = 'https://revelations.me';
  const hubs = [
    {
      name: 'Dubai, UAE',
      pathname: '/locations/dubai-uae',
    },
    {
      name: 'Tbilisi, Georgia',
      pathname: '/locations/tbilisi-georgia',
    },
  ];

  const breadcrumbs =
    buildTaxonomyIndexBreadcrumbJsonLd({
      name: 'Locations',
      pathname: '/locations',
      siteUrl,
    });
  const collection =
    buildTaxonomyIndexCollectionJsonLd({
      name: 'Locations',
      description: 'Explore REVELATIONS by location.',
      pathname: '/locations',
      hubs,
      siteUrl,
    });

  assert.equal(
    breadcrumbs.itemListElement[1].item,
    'https://revelations.me/locations'
  );
  assert.equal(collection['@type'], 'CollectionPage');
  assert.equal(
    collection.url,
    'https://revelations.me/locations'
  );
  assert.equal(
    collection.mainEntity['@type'],
    'ItemList'
  );
  assert.equal(
    collection.mainEntity.numberOfItems,
    2
  );
  assert.deepEqual(
    collection.mainEntity.itemListElement.map(
      (item) => item.url
    ),
    [
      'https://revelations.me/locations/dubai-uae',
      'https://revelations.me/locations/tbilisi-georgia',
    ]
  );
});

test('all collection routes use one dynamic eligibility-driven page', async () => {
  const component = await readFile(
    componentPath,
    'utf8'
  );

  for (const required of [
    'getTaxonomyHubs(type)',
    'getTaxonomyIndexConfig(type)',
    'buildTaxonomyIndexBreadcrumbJsonLd',
    'buildTaxonomyIndexCollectionJsonLd',
    'hubs.map((hub)',
    'hub.seoDescription',
    'hub.articles.length',
  ]) {
    assert.ok(
      component.includes(required),
      `missing shared index contract: ${required}`
    );
  }

  assert.doesNotMatch(
    component,
    /\/(?:topics|series|locations|tags)\/[a-z0-9-]+/
  );
  assert.doesNotMatch(component, /noindex/i);

  for (const [type, path] of Object.entries(ROUTES)) {
    const source = await readFile(path, 'utf8');

    for (const required of [
      `getTaxonomyIndexConfig('${type}')`,
      `<TaxonomyIndexPage type="${type}" />`,
      'buildPageMetadata',
      'pathname: config.pathname',
      "card: 'summary'",
    ]) {
      assert.ok(
        source.includes(required),
        `missing ${type} route contract: ${required}`
      );
    }

    assert.doesNotMatch(
      source,
      /\/(?:topics|series|locations|tags)\/[a-z0-9-]+/
    );
  }
});

test('collection discovery is present in footer and archive but not primary header', async () => {
  const [archive, footer, header] = await Promise.all([
    readFile(archivePath, 'utf8'),
    readFile(footerPath, 'utf8'),
    readFile(headerPath, 'utf8'),
  ]);

  assert.match(
    archive,
    /buildAllTaxonomyHubs\(articles\)/
  );
  assert.ok(
    archive.includes(
      'TAXONOMY_INDEX_ORDER.map'
    )
  );
  assert.ok(
    archive.includes('Explore collections')
  );

  for (const [label, path] of [
    ['Topics', '/topics'],
    ['Series', '/series'],
    ['Locations', '/locations'],
    ['Entities', '/tags'],
  ]) {
    assert.ok(
      footer.includes(`['${label}', '${path}']`)
    );
    assert.equal(
      header.includes(`href: '${path}'`),
      false
    );
  }
});

test('all collection indexes are included in sitemap and signed revalidation', async () => {
  const [sitemap, revalidation] = await Promise.all([
    readFile(sitemapPath, 'utf8'),
    readFile(revalidationPath, 'utf8'),
  ]);

  assert.ok(
    sitemap.includes('const STATIC_PATHS = [')
  );
  assert.ok(
    revalidation.includes('const paths = new Set([')
  );

  for (const value of [
    "'topics'",
    "'series'",
    "'locations'",
    "'tags'",
  ]) {
    assert.ok(
      sitemap.includes(value),
      `sitemap is missing ${value}`
    );
  }

  for (const path of [
    '/topics',
    '/series',
    '/locations',
    '/tags',
  ]) {
    assert.ok(
      revalidation.includes(`'${path}'`),
      `revalidation is missing ${path}`
    );
  }
});
