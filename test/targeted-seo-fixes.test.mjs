import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import {
  buildArticleMetadata,
  stripTerminalBrandSuffix,
} from '../lib/seo.js';

const siteUrl = 'https://revelations.me';
const fallbackImage = '/media/brand/revelations-logo.png';
const openxaiCanonicalPath =
  '/ashton-hettiarachi-how-openxai-will-change-the-worlds-perceptions';

test('both historical OpenxAI paths are matched by one direct permanent proxy redirect', async () => {
  const source = await readFile(
    new URL('../proxy.js', import.meta.url),
    'utf8'
  );

  for (const pathname of [
    '/how-openxai-will-change-the-worlds-perceptions',
    '/article/how-openxai-will-change-the-worlds-perceptions',
  ]) {
    assert.match(source, new RegExp(`'${pathname}'`));
  }

  assert.match(source, new RegExp(`'${openxaiCanonicalPath}'`));
  assert.match(source, /NextResponse\.redirect\(url, 308\)/);
});

test('the targeted proxy matcher does not add other legacy or deleted article paths', async () => {
  const source = await readFile(
    new URL('../proxy.js', import.meta.url),
    'utf8'
  );

  assert.doesNotMatch(source, /69e399ab9b88aa5cd5f7d2c8/);
  assert.doesNotMatch(source, /burnout-doesnt-look-like-burnout/);
});

test('terminal REVELATIONS suffix is removed only for the document title', () => {
  const sourceTitle =
    'Ashton Hettiarachi on OpenxAI and the Future of AI | REVELATIONS';
  const metadata = buildArticleMetadata(
    {
      slug: 'ashton-hettiarachi-how-openxai-will-change-the-worlds-perceptions',
      title: 'Ashton Hettiarachi: How OpenxAI Will Change the World’s Perceptions?',
      seo_title: sourceTitle,
      seo_description: 'A description.',
    },
    { siteUrl, fallbackImage, siteName: 'REVELATIONS' }
  );

  assert.equal(
    metadata.title,
    'Ashton Hettiarachi on OpenxAI and the Future of AI'
  );
  assert.equal(metadata.openGraph.title, sourceTitle);
  assert.equal(metadata.twitter.title, sourceTitle);
  assert.equal(
    metadata.alternates.canonical,
    `${siteUrl}${openxaiCanonicalPath}`
  );
});

test('unbranded titles and REVELATIONS in title content are preserved', () => {
  assert.equal(
    stripTerminalBrandSuffix('REVELATIONS of Tomorrow', 'REVELATIONS'),
    'REVELATIONS of Tomorrow'
  );
  assert.equal(
    stripTerminalBrandSuffix('A title - REVELATIONS', 'REVELATIONS'),
    'A title'
  );

  const metadata = buildArticleMetadata(
    {
      slug: 'normal-article',
      title: 'Normal article',
      seo_title: 'Normal SEO title',
      seo_description: 'A description.',
    },
    { siteUrl, fallbackImage, siteName: 'REVELATIONS' }
  );

  assert.equal(metadata.title, 'Normal SEO title');
  assert.equal(metadata.openGraph.title, 'Normal SEO title');
  assert.equal(metadata.twitter.title, 'Normal SEO title');
});
