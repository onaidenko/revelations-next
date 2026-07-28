import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import { buildPageMetadata } from '../lib/seo.js';

const siteUrl = 'https://revelations.me';
const staticRoutes = {
  'app/about/page.jsx': '/about',
  'app/advertise/page.jsx': '/advertise',
  'app/access/page.jsx': '/access',
  'app/contact/page.jsx': '/contact',
  'app/archive/page.jsx': '/archive',
  'app/news/page.jsx': '/news',
  'app/people/page.jsx': '/people',
  'app/tech/page.jsx': '/tech',
  'app/places/page.jsx': '/places',
  'app/unspoken/page.jsx': '/unspoken',
  'app/podcast/page.jsx': '/podcast',
};

test('page metadata keeps canonical and Open Graph URL route-specific', () => {
  for (const pathname of Object.values(staticRoutes)) {
    const metadata = buildPageMetadata({
      title: 'Route title',
      description: 'Route description',
      pathname,
      siteUrl,
    });
    const expected = `${siteUrl}${pathname}`;

    assert.equal(metadata.alternates.canonical, expected);
    assert.equal(metadata.openGraph.url, expected);
    assert.equal(metadata.twitter.title, 'Route title');
    assert.equal(metadata.twitter.description, 'Route description');
    assert.equal(
      metadata.twitter.images[0].url,
      'https://revelations.me/media/brand/revelations-logo.png'
    );
  }
});

test('every public sitemap page delegates its metadata to the shared helper', async () => {
  for (const [file, pathname] of Object.entries(staticRoutes)) {
    const source = await readFile(new URL(`../${file}`, import.meta.url), 'utf8');

    assert.match(source, /buildPageMetadata/);
    assert.match(source, /siteUrl: SITE_URL/);

    if (source.includes('const SECTION =')) {
      assert.match(
        source,
        new RegExp(`const SECTION = '${pathname.slice(1)}';`)
      );
      assert.match(source, /pathname: `\/\$\{SECTION\}`/);
    } else {
      assert.match(source, new RegExp(`pathname: '${pathname}'`));
    }
  }
});

test('About uses its approved unique metadata description', async () => {
  const about = await readFile(
    new URL('../app/about/page.jsx', import.meta.url),
    'utf8'
  );

  assert.match(
    about,
    /Learn how REVELATIONS, a Dubai-based future-facing media platform, covers technology, founders, culture, places and the ideas shaping what comes next\./
  );
  assert.doesNotMatch(about, /description: DEFAULT_DESCRIPTION/);
});

test('Podcast uses its approved page-specific metadata and visible introduction', async () => {
  const podcast = await readFile(
    new URL('../app/podcast/page.jsx', import.meta.url),
    'utf8'
  );
  const sectionPage = await readFile(
    new URL('../components/section-page.jsx', import.meta.url),
    'utf8'
  );

  assert.match(
    podcast,
    /REVELATIONS Podcast - Dubai Tech & Founder Conversations/
  );
  assert.match(
    podcast,
    /A Dubai-based podcast featuring founders, investors and builders across AI, fintech, Web3 and culture\. Watch REVELATIONS episodes and explore the stories behind the future\./
  );
  assert.match(
    podcast,
    /title:\s*\{\s*absolute: PODCAST_TITLE/
  );
  assert.match(podcast, /buildPodcastSeriesJsonLd/);
  assert.match(
    podcast,
    /Based in Dubai, REVELATIONS brings together conversations with founders, investors and builders across AI, fintech, Web3, technology and culture/
  );
  assert.match(sectionPage, /\{introduction\}/);
});
