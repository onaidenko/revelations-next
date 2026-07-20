import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import {
  buildTaxonomyIndexBreadcrumbJsonLd,
  buildTaxonomyIndexCollectionJsonLd,
} from '../lib/seo.js';

const pagePath = new URL(
  '../app/topics/page.jsx',
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

test('topics index schemas expose one canonical collection', () => {
  const siteUrl = 'https://revelations.me';
  const hubs = [
    {
      name: 'AI & Data',
      pathname: '/topics/ai-data',
    },
    {
      name: 'Robotics & Automation',
      pathname: '/topics/robotics-automation',
    },
  ];

  const breadcrumbs =
    buildTaxonomyIndexBreadcrumbJsonLd({
      name: 'Topics',
      pathname: '/topics',
      siteUrl,
    });
  const collection =
    buildTaxonomyIndexCollectionJsonLd({
      name: 'Topics',
      description: 'Explore REVELATIONS by topic.',
      pathname: '/topics',
      hubs,
      siteUrl,
    });

  assert.equal(
    breadcrumbs.itemListElement[1].item,
    'https://revelations.me/topics'
  );
  assert.equal(collection['@type'], 'CollectionPage');
  assert.equal(
    collection.url,
    'https://revelations.me/topics'
  );
  assert.equal(collection.mainEntity['@type'], 'ItemList');
  assert.equal(collection.mainEntity.numberOfItems, 2);
  assert.deepEqual(
    collection.mainEntity.itemListElement.map(
      (item) => item.url
    ),
    [
      'https://revelations.me/topics/ai-data',
      'https://revelations.me/topics/robotics-automation',
    ]
  );
});

test('topics page is dynamic, indexable and contains no manual topic inventory', async () => {
  const source = await readFile(pagePath, 'utf8');

  for (const required of [
    "getTaxonomyHubs('topics')",
    "pathname: '/topics'",
    'buildTaxonomyIndexBreadcrumbJsonLd',
    'buildTaxonomyIndexCollectionJsonLd',
    "card: 'summary'",
    'topics.map((topic)',
    'topic.seoDescription',
    'topic.articles.length',
  ]) {
    assert.ok(
      source.includes(required),
      `missing topics-page contract: ${required}`
    );
  }

  assert.doesNotMatch(
    source,
    /\/topics\/(?:ai-data|robotics-automation|startups-founders-investment)/
  );
  assert.doesNotMatch(source, /noindex/i);
});

test('topics discovery is present in footer and archive but not primary header', async () => {
  const [archive, footer, header] = await Promise.all([
    readFile(archivePath, 'utf8'),
    readFile(footerPath, 'utf8'),
    readFile(headerPath, 'utf8'),
  ]);

  assert.match(
    archive,
    /buildTaxonomyHubs\(\s*articles,\s*'topics'\s*\)/
  );
  assert.ok(archive.includes('Explore by topic'));
  assert.ok(archive.includes('href="/topics"'));
  assert.ok(footer.includes("['Topics', '/topics']"));
  assert.equal(header.includes("href: '/topics'"), false);
});

test('topics index is included in sitemap and signed revalidation', async () => {
  const [sitemap, revalidation] = await Promise.all([
    readFile(sitemapPath, 'utf8'),
    readFile(revalidationPath, 'utf8'),
  ]);

  assert.match(
    sitemap,
    /const STATIC_PATHS = \[[\s\S]*'topics'/
  );
  assert.match(
    revalidation,
    /const paths = new Set\(\[[\s\S]*'\/topics'/
  );
});
