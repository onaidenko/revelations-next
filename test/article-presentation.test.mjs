import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

import {
  buildArticleHeaderLabels,
  countArticleWords,
  formatReadingTime,
  readingTimeMinutes,
} from '../lib/article-presentation.js';

test('reading time counts article HTML and Markdown deterministically', () => {
  assert.equal(countArticleWords(''), 0);
  assert.equal(formatReadingTime(''), '');
  assert.equal(readingTimeMinutes('<p>One short body.</p>'), 1);
  assert.equal(readingTimeMinutes('word '.repeat(220)), 1);
  assert.equal(readingTimeMinutes('word '.repeat(221)), 2);
  assert.equal(readingTimeMinutes(`<h2>Title</h2><p>${'word '.repeat(439)}</p>`), 2);
  assert.equal(countArticleWords('<a href="https://example.com">Linked words</a>'), 2);
  assert.equal(countArticleWords('## Heading\n\n[Linked words](https://example.com)'), 3);
});

test('header labels prioritize section and assigned taxonomy without duplicates', () => {
  const labels = buildArticleHeaderLabels({
    section: 'people',
    sectionName: 'People',
    taxonomy: {
      primaryTopic: { name: 'Fintech', slug: 'fintech', href: '/topics/fintech' },
      secondaryTopics: [
        { name: 'Payments', slug: 'payments', href: '/topics/payments' },
        { name: 'AI', slug: 'ai', href: '/topics/ai' },
      ],
      series: [],
      locations: [],
      tags: [],
    },
  });

  assert.deepEqual(labels.map(({ name }) => name), ['People', 'Fintech', 'Payments']);
  assert.equal(labels.length, 3);
  assert.equal(labels[1].href, '/topics/fintech');

  const fallback = buildArticleHeaderLabels({
    section: 'news',
    sectionName: 'News',
    taxonomy: {
      primaryTopic: null,
      secondaryTopics: [],
      series: [{ name: 'News', slug: 'news', href: '/series/news' }],
      locations: [{ name: 'Dubai', slug: 'dubai', href: '/locations/dubai' }],
      tags: [],
    },
  });

  assert.deepEqual(fallback.map(({ name }) => name), ['News', 'Dubai']);
});

test('article route preserves SEO/schema helpers and exposes semantic header markup', () => {
  const source = readFileSync(new URL('../app/[slug]/page.jsx', import.meta.url), 'utf8');

  assert.match(source, /<main>/);
  assert.match(source, /<article/);
  assert.match(source, /<time dateTime=\{publicationDate\}>/);
  assert.match(source, /buildArticleHeaderLabels/);
  assert.match(source, /formatReadingTime\(article\.content\)/);
  assert.match(source, /buildArticleMetadata/);
  assert.match(source, /buildArticleJsonLd/);
  assert.match(source, /buildArticleBreadcrumbJsonLd/);
  assert.equal((source.match(/<h1[\s>]/g) || []).length, 1);
  assert.match(source, /article\.excerpt &&/);
  assert.doesNotMatch(source, /article\.seo_description/);
});
