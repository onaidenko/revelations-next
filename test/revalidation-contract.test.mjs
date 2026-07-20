import assert from 'node:assert/strict';
import { createHmac } from 'node:crypto';
import test from 'node:test';

import { CMS_ARTICLES_CACHE_TAG } from '../lib/cache-tags.js';
import {
  MAX_REVALIDATION_BODY_BYTES,
  affectedPaths,
  parsePayload,
  validSignature,
  validTimestamp,
} from '../lib/revalidation-contract.js';

const payload = {
  version: 1,
  event_id: 'e1',
  post_id: 214,
  post_type: 'post',
  action: 'publish',
  old_status: 'draft',
  new_status: 'publish',
  old_slug: null,
  new_slug: 'new-story',
  old_section: null,
  new_section: 'news',
  occurred_at: '2026-07-19T00:00:00Z',
};

const body = JSON.stringify(payload);
const secret = 'test-only';
const timestamp = String(
  Math.floor(Date.now() / 1000)
);
const now = Number(timestamp) * 1000;
const sign = (
  value = body,
  time = timestamp
) =>
  `sha256=${createHmac('sha256', secret)
    .update(`${time}.${value}`)
    .digest('hex')}`;

const COLLECTION_PATHS = [
  '/',
  '/archive',
  '/topics',
  '/series',
  '/locations',
  '/tags',
  '/sitemap.xml',
  '/news-sitemap.xml',
];

test('validates exact signed bodies and cache tag', () => {
  assert.ok(
    validSignature(
      sign(),
      timestamp,
      body,
      secret
    )
  );
  assert.ok(
    !validSignature(
      sign(),
      timestamp,
      `${body} `,
      secret
    )
  );
  assert.equal(
    CMS_ARTICLES_CACHE_TAG,
    'revelations:cms:articles'
  );
});

test('rejects malformed auth and timestamps', () => {
  assert.ok(
    !validSignature(
      'sha256=ABC',
      timestamp,
      body,
      secret
    )
  );
  assert.ok(
    !validSignature(
      `sha256=${'A'.repeat(64)}`,
      timestamp,
      body,
      secret
    )
  );
  assert.ok(!validTimestamp('x', now));
  assert.ok(
    !validTimestamp(
      String(Number(timestamp) - 301),
      now
    )
  );
  assert.ok(
    !validTimestamp(
      String(Number(timestamp) + 301),
      now
    )
  );
});

test('rejects malformed or unsafe payloads', () => {
  for (const change of [
    { version: 2 },
    { event_id: '' },
    { post_id: 0 },
    { post_type: 'page' },
    { action: 'x' },
    { occurred_at: 'nope' },
    { new_slug: '../x' },
    { new_slug: 'https://x' },
    { new_slug: 'x?y' },
    { new_slug: 'X' },
    { new_section: 'other' },
    { new_taxonomy_paths: ['/topics'] },
    { new_taxonomy_paths: ['/tags/Bad-Slug'] },
  ]) {
    assert.equal(
      parsePayload(
        JSON.stringify({
          ...payload,
          ...change,
        })
      ),
      null
    );
  }

  assert.equal(parsePayload('{'), null);
  assert.ok(
    Buffer.byteLength(
      'x'.repeat(
        MAX_REVALIDATION_BODY_BYTES + 1
      )
    ) > MAX_REVALIDATION_BODY_BYTES
  );
});

test('plans all collection indexes plus canonical event paths', () => {
  assert.deepEqual(
    affectedPaths(parsePayload(body)),
    [
      ...COLLECTION_PATHS,
      '/new-story',
      '/news',
    ]
  );

  const changed = parsePayload(
    JSON.stringify({
      ...payload,
      action: 'update',
      old_slug: 'old',
      new_slug: 'new',
      old_section: 'tech',
      new_section: 'news',
    })
  );
  assert.deepEqual(
    affectedPaths(changed),
    [
      ...COLLECTION_PATHS,
      '/old',
      '/new',
      '/tech',
      '/news',
    ]
  );

  const taxonomyChanged = parsePayload(
    JSON.stringify({
      ...payload,
      old_taxonomy_paths: [
        '/topics/ai-data',
      ],
      new_taxonomy_paths: [
        '/tags/openai',
        '/topics/ai-data',
      ],
    })
  );
  assert.deepEqual(
    affectedPaths(taxonomyChanged),
    [
      ...COLLECTION_PATHS,
      '/new-story',
      '/news',
      '/topics/ai-data',
      '/tags/openai',
    ]
  );

  const removed = parsePayload(
    JSON.stringify({
      ...payload,
      action: 'delete',
      old_slug: 'old',
      new_slug: null,
      old_section: 'news',
      new_section: null,
    })
  );
  assert.ok(
    affectedPaths(removed).includes('/old')
  );

  const deduplicated = affectedPaths(
    parsePayload(
      JSON.stringify({
        ...payload,
        old_slug: 'same',
        new_slug: 'same',
        old_section: 'news',
        new_section: 'news',
      })
    )
  );
  assert.equal(
    new Set(deduplicated).size,
    10
  );
});
