import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { scoreRelatedCandidate, selectRelatedArticles } from '../lib/related-articles.js';

const map = JSON.parse(readFileSync(new URL('../data/seo/editorial-taxonomy-v2.json', import.meta.url)));

test('approved taxonomy map is complete and internally valid', () => {
  assert.equal(map.schema_version, 2); assert.equal(map.status, 'approved_for_implementation');
  assert.equal(map.topics.length, 9); assert.equal(map.series.length, 4); assert.equal(map.assignments.length, 45); assert.equal(map.manual_related.length, 5);
  const slugs = new Set(map.assignments.map((item) => item.slug)); const topics = new Set(map.topics.map((item) => item.slug)); const series = new Set(map.series.map((item) => item.slug));
  assert.equal(slugs.size, 45);
  for (const item of map.assignments) { assert.ok(topics.has(item.primary_topic)); assert.ok(item.secondary_topics.length <= 2); assert.ok(!item.secondary_topics.includes(item.primary_topic)); assert.equal(new Set(item.secondary_topics).size, item.secondary_topics.length); assert.ok(item.secondary_topics.every((topic) => topics.has(topic))); assert.ok(item.series === null || series.has(item.series)); }
  for (const item of map.manual_related) { assert.ok(slugs.has(item.source_slug)); assert.ok(!item.target_slugs.includes(item.source_slug)); assert.equal(new Set(item.target_slugs).size, item.target_slugs.length); assert.ok(item.target_slugs.every((slug) => slugs.has(slug))); }
  assert.deepEqual(map.assignments.filter((item) => !item.public_topic_eligible).map((item) => item.slug), ['33-qs-for-sergei-medvedev', '33-qs-for-timur-makhmudi']);
});

const article = (slug, patch = {}) => ({ slug, status: 'published', section: 'news', publication_date: '2026-01-01', primary_topic: '', topics: [], series: '', manual_related: [], ...patch });

test('related scoring keeps semantic priority and deterministic order', () => {
  const source = article('source', { primary_topic: 'ai', topics: ['ai', 'work'], series: 's', manual_related: ['manual-b', 'manual-a'] });
  const manualA = article('manual-a', { section: 'people' }); const manualB = article('manual-b');
  const sameSeries = article('series', { series: 's' }); const samePrimary = article('primary', { primary_topic: 'ai' }); const primarySecondary = article('primary-secondary', { primary_topic: 'tech', topics: ['tech', 'ai'] }); const sharedSecondary = article('shared-secondary', { primary_topic: 'other', topics: ['other', 'work'] });
  assert.ok(scoreRelatedCandidate(source, sameSeries).score > scoreRelatedCandidate(source, samePrimary).score);
  assert.ok(scoreRelatedCandidate(source, samePrimary).score > scoreRelatedCandidate(source, article('section')).score);
  assert.ok(scoreRelatedCandidate(source, primarySecondary).score > scoreRelatedCandidate(source, sharedSecondary).score);
  assert.deepEqual(selectRelatedArticles(source, [samePrimary, manualA, sameSeries, manualB], 3).map((item) => item.slug), ['manual-b', 'manual-a', 'series']);
  assert.deepEqual(selectRelatedArticles(source, [samePrimary, sameSeries, source, article('draft', { status: 'draft' })], 3).map((item) => item.slug), ['series', 'primary']);
});

test('related fallback is unique, bounded and does not privilege newest section records', () => {
  const source = article('old', { publication_date: '2010-01-01' });
  const close = article('close', { publication_date: '2010-01-02' }); const newest = article('newest', { publication_date: '2026-01-01' }); const other = article('other', { publication_date: '2011-01-01' });
  const result = selectRelatedArticles(source, [newest, close, other, close], 3).map((item) => item.slug);
  assert.deepEqual(result, ['close', 'other', 'newest']); assert.equal(new Set(result).size, result.length);
});

test('all approved fixtures select three unique related articles', () => {
  const manual = new Map(map.manual_related.map((item) => [item.source_slug, item.target_slugs]));
  const corpus = map.assignments.map((item, index) => article(item.slug, {
    section: index % 2 ? 'news' : 'people', publication_date: `2026-01-${String((index % 28) + 1).padStart(2, '0')}`,
    primary_topic: item.primary_topic, topics: [item.primary_topic, ...item.secondary_topics], series: item.series || '', manual_related: manual.get(item.slug) || [],
  }));
  for (const source of corpus) {
    const related = selectRelatedArticles(source, corpus);
    assert.equal(related.length, 3); assert.equal(new Set(related.map((item) => item.slug)).size, 3);
  }
});
