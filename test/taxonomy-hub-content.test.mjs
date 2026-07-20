import assert from 'node:assert/strict';
import test from 'node:test';

import {
  TAXONOMY_HUB_CONTENT,
  getTaxonomyHubContent,
} from '../lib/taxonomy-hub-content.js';
import {
  buildTaxonomyHubs,
} from '../lib/taxonomy-hubs.js';
import {
  buildTaxonomyCollectionJsonLd,
  buildTaxonomyMetadata,
} from '../lib/seo.js';

const EXPECTED = {
  topics: 9,
  series: 3,
  locations: 4,
  tags: 9,
};

const words = (value) =>
  String(value).match(/\b[\w’'-]+\b/gu)?.length || 0;

test('SEO 3A content covers exactly the 25 approved public hubs', () => {
  assert.deepEqual(
    Object.fromEntries(
      Object.entries(TAXONOMY_HUB_CONTENT).map(
        ([type, entries]) => [type, Object.keys(entries).length]
      )
    ),
    EXPECTED
  );

  const titles = new Set();
  const descriptions = new Set();
  const introductions = new Set();

  for (const [type, entries] of Object.entries(
    TAXONOMY_HUB_CONTENT
  )) {
    for (const [slug, value] of Object.entries(entries)) {
      const finalTitle = `${value.seoTitle} — REVELATIONS`;

      assert.match(slug, /^[a-z0-9]+(?:-[a-z0-9]+)*$/);
      assert.ok(finalTitle.length >= 25);
      assert.ok(finalTitle.length <= 65);
      assert.ok(value.seoDescription.length >= 90);
      assert.ok(value.seoDescription.length <= 180);
      assert.ok(words(value.introduction) >= 45);
      assert.ok(words(value.introduction) <= 90);

      assert.equal(titles.has(value.seoTitle), false);
      assert.equal(
        descriptions.has(value.seoDescription),
        false
      );
      assert.equal(
        introductions.has(value.introduction),
        false
      );

      titles.add(value.seoTitle);
      descriptions.add(value.seoDescription);
      introductions.add(value.introduction);

      assert.deepEqual(
        getTaxonomyHubContent(type, slug),
        value
      );
    }
  }
});

test('built hubs receive editorial content without changing eligibility', () => {
  const articles = [
    {
      slug: 'one',
      title: 'One',
      status: 'published',
      publication_date: '2026-01-01',
      tag_terms: [{ slug: 'openai', name: 'OpenAI' }],
    },
    {
      slug: 'two',
      title: 'Two',
      status: 'published',
      publication_date: '2026-01-02',
      tag_terms: [{ slug: 'openai', name: 'OpenAI' }],
    },
  ];

  const [hub] = buildTaxonomyHubs(articles, 'tags');
  const expected = TAXONOMY_HUB_CONTENT.tags.openai;

  assert.equal(hub.slug, 'openai');
  assert.equal(hub.articles.length, 2);
  assert.equal(hub.seoTitle, expected.seoTitle);
  assert.equal(
    hub.seoDescription,
    expected.seoDescription
  );
  assert.equal(
    hub.introduction,
    expected.introduction
  );
});

test('taxonomy metadata and CollectionPage use the curated copy', () => {
  const content = TAXONOMY_HUB_CONTENT.topics['ai-data'];
  const hub = {
    type: 'topics',
    slug: 'ai-data',
    name: 'AI & Data',
    pathname: '/topics/ai-data',
    articles: [
      {
        slug: 'one',
        title: 'One',
      },
    ],
    ...content,
  };

  const metadata = buildTaxonomyMetadata(hub, {
    siteUrl: 'https://revelations.me',
  });
  const collection = buildTaxonomyCollectionJsonLd(
    hub,
    'https://revelations.me'
  );

  assert.equal(metadata.title, content.seoTitle);
  assert.equal(
    metadata.description,
    content.seoDescription
  );
  assert.equal(
    metadata.openGraph.title,
    content.seoTitle
  );
  assert.equal(
    metadata.openGraph.description,
    content.seoDescription
  );
  assert.equal(
    metadata.twitter.description,
    content.seoDescription
  );
  assert.equal(
    collection.description,
    content.seoDescription
  );
});

test('unknown or future hubs retain safe metadata fallbacks', () => {
  const hub = {
    type: 'series',
    slug: 'future-files',
    name: 'Future Files',
    pathname: '/series/future-files',
    articles: [
      {
        slug: 'one',
        title: 'One',
      },
    ],
  };

  const metadata = buildTaxonomyMetadata(hub, {
    siteUrl: 'https://revelations.me',
  });

  assert.equal(metadata.title, 'Future Files');
  assert.match(metadata.description, /Explore 1 story/);
  assert.equal(
    getTaxonomyHubContent('series', 'future-files'),
    null
  );
});
