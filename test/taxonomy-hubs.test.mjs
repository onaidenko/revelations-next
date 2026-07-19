import assert from 'node:assert/strict';
import test from 'node:test';

import {
  normalizeCmsTerm,
  normalizeCmsTerms,
} from '../lib/cms-taxonomy.js';
import {
  articleIsTopicEligible,
  buildAllTaxonomyHubs,
  buildArticleTaxonomyPresentation,
  buildTaxonomyHubs,
  findTaxonomyHub,
} from '../lib/taxonomy-hubs.js';
import {
  scoreRelatedCandidate,
  selectRelatedArticles,
} from '../lib/related-articles.js';
import {
  buildMainSitemapEntries,
  buildNewsSitemapXml,
  buildTaxonomyBreadcrumbJsonLd,
  buildTaxonomyCollectionJsonLd,
  buildTaxonomyMetadata,
} from '../lib/seo.js';

const article = (slug, patch = {}) => ({
  slug,
  title: slug,
  status: 'published',
  section: 'news',
  publication_date: '2026-01-10T00:00:00Z',
  modified_date: '2026-01-10T00:00:00Z',
  primary_topic: '',
  primary_topic_term: null,
  topics: [],
  topic_terms: [],
  series: '',
  series_term: null,
  locations: [],
  location_terms: [],
  tags: [],
  tag_terms: [],
  public_topic_eligible: true,
  taxonomy_status: 'approved',
  manual_related: [],
  ...patch,
});

test('Related keeps manual, series, topic and section priorities', () => {
  const source = article('source', {
    primary_topic: 'ai',
    topics: ['ai', 'work'],
    series: 'series-a',
    manual_related: ['manual'],
  });
  const manual = article('manual', {
    section: 'people',
  });
  const sameSeries = article('same-series', {
    series: 'series-a',
  });
  const samePrimary = article('same-primary', {
    primary_topic: 'ai',
  });
  const secondary = article('secondary', {
    primary_topic: 'other',
    topics: ['other', 'work'],
  });
  const sameSection = article('same-section');

  assert.ok(
    scoreRelatedCandidate(source, sameSeries).score >
      scoreRelatedCandidate(source, samePrimary).score
  );
  assert.ok(
    scoreRelatedCandidate(source, samePrimary).score >
      scoreRelatedCandidate(source, secondary).score
  );
  assert.ok(
    scoreRelatedCandidate(source, secondary).score >
      scoreRelatedCandidate(
        source,
        article('other-section', {
          section: 'people',
        })
      ).score
  );

  assert.deepEqual(
    selectRelatedArticles(
      source,
      [
        samePrimary,
        sameSeries,
        manual,
        sameSection,
      ]
    ).map(({ slug }) => slug),
    ['manual', 'same-series', 'same-primary']
  );
});

test('Related excludes self, drafts and duplicates and caps at three', () => {
  const source = article('source');
  const closeA = article('a', {
    publication_date: '2026-01-09T00:00:00Z',
  });
  const closeB = article('b', {
    publication_date: '2026-01-09T00:00:00Z',
  });
  const result = selectRelatedArticles(
    source,
    [
      source,
      closeB,
      closeA,
      closeA,
      article('draft', { status: 'draft' }),
      article('fourth'),
    ],
    99
  );

  assert.deepEqual(
    result.map(({ slug }) => slug),
    ['fourth', 'a', 'b']
  );
  assert.equal(
    new Set(result.map(({ slug }) => slug)).size,
    3
  );
});

test('taxonomy hubs apply topic eligibility and thin-page thresholds', () => {
  const corpus = [
    article('one', {
      topic_terms: [
        { slug: 'ai-data', name: 'AI & Data' },
      ],
      primary_topic: 'ai-data',
      location_terms: [
        { slug: 'dubai-uae', name: 'Dubai, UAE' },
      ],
      tag_terms: [
        { slug: 'openai', name: 'OpenAI' },
      ],
      series_term: {
        slug: 'future-files',
        name: 'Future Files',
      },
    }),
    article('two', {
      topic_terms: [
        { slug: 'ai-data', name: 'AI & Data' },
      ],
      primary_topic: 'ai-data',
      location_terms: [
        { slug: 'dubai-uae', name: 'Dubai, UAE' },
      ],
      tag_terms: [
        { slug: 'openai', name: 'OpenAI' },
      ],
      series_term: {
        slug: 'future-files',
        name: 'Future Files',
      },
    }),
    article('excluded', {
      topic_terms: [
        { slug: 'private-topic', name: 'Private' },
      ],
      primary_topic: 'private-topic',
      public_topic_eligible: false,
      taxonomy_status: 'needs-editorial-review',
      tag_terms: [
        { slug: 'single', name: 'Single' },
      ],
      location_terms: [
        { slug: 'london-uk', name: 'London, UK' },
      ],
    }),
  ];

  assert.equal(
    articleIsTopicEligible(corpus[2]),
    false
  );
  assert.deepEqual(
    buildTaxonomyHubs(corpus, 'topics').map(
      ({ slug }) => slug
    ),
    ['ai-data']
  );
  assert.deepEqual(
    buildTaxonomyHubs(corpus, 'series').map(
      ({ slug }) => slug
    ),
    ['future-files']
  );
  assert.deepEqual(
    buildTaxonomyHubs(corpus, 'locations').map(
      ({ slug }) => slug
    ),
    ['dubai-uae']
  );
  assert.deepEqual(
    buildTaxonomyHubs(corpus, 'tags').map(
      ({ slug }) => slug
    ),
    ['openai']
  );
  assert.equal(
    findTaxonomyHub(
      corpus,
      'topics',
      'private-topic'
    ),
    null
  );
  assert.equal(
    findTaxonomyHub(
      corpus,
      'tags',
      'single'
    ),
    null
  );
  assert.equal(
    findTaxonomyHub(corpus, 'tags', '../openai'),
    null
  );
});

test('article taxonomy links only point to existing public hubs', () => {
  const source = article('one', {
    topic_terms: [
      { slug: 'ai-data', name: 'AI & Data' },
      { slug: 'work', name: 'Future of Work' },
    ],
    primary_topic: 'ai-data',
    primary_topic_term: {
      slug: 'ai-data',
      name: 'AI & Data',
    },
    location_terms: [
      { slug: 'dubai-uae', name: 'Dubai, UAE' },
      { slug: 'single-place', name: 'Single Place' },
    ],
    tag_terms: [
      { slug: 'openai', name: 'OpenAI' },
      { slug: 'single-tag', name: 'Single Tag' },
    ],
  });
  const second = article('two', {
    topic_terms: [
      { slug: 'ai-data', name: 'AI & Data' },
      { slug: 'work', name: 'Future of Work' },
    ],
    primary_topic: 'work',
    location_terms: [
      { slug: 'dubai-uae', name: 'Dubai, UAE' },
    ],
    tag_terms: [
      { slug: 'openai', name: 'OpenAI' },
    ],
  });
  const hubs = buildAllTaxonomyHubs([
    source,
    second,
  ]);
  const value = buildArticleTaxonomyPresentation(
    source,
    hubs
  );

  assert.equal(
    value.primaryTopic.href,
    '/topics/ai-data'
  );
  assert.equal(
    value.locations.find(
      ({ slug }) => slug === 'dubai-uae'
    ).href,
    '/locations/dubai-uae'
  );
  assert.equal(
    value.locations.find(
      ({ slug }) => slug === 'single-place'
    ).href,
    null
  );
  assert.equal(
    value.tags.find(
      ({ slug }) => slug === 'single-tag'
    ).href,
    null
  );
});

test('taxonomy metadata, schemas and sitemap use one canonical URL', () => {
  const articles = [
    article('one'),
    article('two', {
      publication_date: '2026-01-11T00:00:00Z',
      modified_date: '2026-01-12T00:00:00Z',
    }),
  ];
  const hub = {
    type: 'tags',
    slug: 'openai',
    name: 'OpenAI',
    pathname: '/tags/openai',
    articles,
  };
  const metadata = buildTaxonomyMetadata(hub, {
    siteUrl: 'https://revelations.me',
  });
  const breadcrumb =
    buildTaxonomyBreadcrumbJsonLd(
      hub,
      'https://revelations.me'
    );
  const collection =
    buildTaxonomyCollectionJsonLd(
      hub,
      'https://revelations.me'
    );
  const sitemap = buildMainSitemapEntries({
    articles,
    siteUrl: 'https://revelations.me',
    staticPaths: [''],
    sectionPaths: ['news'],
    taxonomyHubs: [hub, hub],
  });
  const taxonomyEntries = sitemap.filter(
    ({ url }) =>
      url === 'https://revelations.me/tags/openai'
  );

  assert.equal(
    metadata.alternates.canonical,
    metadata.openGraph.url
  );
  assert.equal(
    metadata.alternates.canonical,
    breadcrumb.itemListElement.at(-1).item
  );
  assert.equal(
    metadata.alternates.canonical,
    collection.url
  );
  assert.equal(taxonomyEntries.length, 1);
  assert.equal(
    taxonomyEntries[0].lastModified.toISOString(),
    '2026-01-12T00:00:00.000Z'
  );
  assert.doesNotMatch(
    buildNewsSitemapXml(articles, {
      siteUrl: 'https://revelations.me',
      now: new Date(
        '2026-01-11T12:00:00Z'
      ),
    }),
    /topics|series|locations|tags/
  );
});

test('CMS term names decode HTML entities without changing slugs', () => {
  const tag = normalizeCmsTerm({
    slug: 'dolce-gabbana',
    name: 'Dolce &amp; Gabbana',
  });
  const topics = normalizeCmsTerms([
    {
      slug: 'ai-data',
      name: 'AI &amp; Data',
    },
    {
      slug: 'ai-data',
      name: 'Duplicate ignored',
    },
  ]);

  assert.equal(tag.name, 'Dolce & Gabbana');
  assert.equal(tag.slug, 'dolce-gabbana');
  assert.equal(topics[0].name, 'AI & Data');
  assert.equal(topics[0].slug, 'ai-data');
  assert.equal(topics.length, 1);
});
