import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import {
  EDITORIAL_SECTION_ORDER,
  featuredStorySide,
} from '../lib/sections.js';

const expectedOrder = [
  'news',
  'people',
  'tech',
  'places',
  'art',
  'unspoken',
];

test('editorial featured stories alternate from News on the right', () => {
  assert.deepEqual(
    EDITORIAL_SECTION_ORDER,
    expectedOrder
  );

  for (const [index, sectionId] of expectedOrder.entries()) {
    assert.equal(
      featuredStorySide(sectionId),
      index % 2 === 0 ? 'right' : 'left'
    );
  }
});

test('non-editorial sections do not enter the featured-side alternation', () => {
  assert.equal(featuredStorySide('podcast'), null);
  assert.equal(featuredStorySide('access'), null);
});

test('SectionPage uses the shared editorial-side resolver only for desktop reversal', async () => {
  const source = await readFile(
    new URL('../components/section-page.jsx', import.meta.url),
    'utf8'
  );

  assert.match(
    source,
    /featuredStorySide\(sectionId\) === 'right'/
  );
  assert.match(source, /md:flex-row-reverse/);
  assert.doesNotMatch(source, /STICKY_RIGHT/);
});
