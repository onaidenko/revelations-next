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
