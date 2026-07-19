import assert from 'node:assert/strict';
import test from 'node:test';

import {
  affectedPaths,
  parsePayload,
} from '../lib/revalidation-contract.js';

const base = {
  version: 1,
  event_id: 'taxonomy-event',
  post_id: 1,
  post_type: 'post',
  action: 'update',
  old_status: 'publish',
  new_status: 'publish',
  old_slug: 'old-story',
  new_slug: 'new-story',
  old_section: 'news',
  new_section: 'tech',
  occurred_at: '2026-07-19T00:00:00Z',
};

test('v1 remains backward compatible without taxonomy arrays', () => {
  const parsed = parsePayload(
    JSON.stringify(base)
  );

  assert.deepEqual(
    parsed.old_taxonomy_paths,
    []
  );
  assert.deepEqual(
    parsed.new_taxonomy_paths,
    []
  );
});

test('taxonomy paths are bounded, canonical and deduplicated', () => {
  const parsed = parsePayload(
    JSON.stringify({
      ...base,
      old_taxonomy_paths: [
        '/tags/openai',
        '/tags/openai',
        '/locations/dubai-uae',
      ],
      new_taxonomy_paths: [
        '/topics/ai-data',
        '/series/future-files',
      ],
    })
  );
  const paths = affectedPaths(parsed);

  for (const path of [
    '/tags/openai',
    '/locations/dubai-uae',
    '/topics/ai-data',
    '/series/future-files',
    '/sitemap.xml',
  ]) {
    assert.ok(paths.includes(path));
  }

  assert.equal(
    paths.filter(
      (path) => path === '/tags/openai'
    ).length,
    1
  );

  for (const invalid of [
    ['/tag/openai'],
    ['/tags/OpenAI'],
    ['/tags/../openai'],
    ['https://revelations.me/tags/openai'],
    Array.from(
      { length: 129 },
      (_, index) => `/tags/tag-${index}`
    ),
  ]) {
    assert.equal(
      parsePayload(
        JSON.stringify({
          ...base,
          new_taxonomy_paths: invalid,
        })
      ),
      null
    );
  }
});
