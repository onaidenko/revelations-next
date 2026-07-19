import assert from 'node:assert/strict';
import test from 'node:test';

import {
  articleCanonicalUrl,
  buildPageMetadata,
  buildArticleBreadcrumbJsonLd,
  buildArticleJsonLd,
  buildArticleMetadata,
  buildMainSitemapEntries,
  buildNewsSitemapXml,
  buildOrganizationJsonLd,
  buildWebsiteJsonLd,
  normalizeSiteUrl,
  toAbsoluteUrl,
} from '../lib/seo.js';

const siteUrl = 'https://revelations.me/';
const organizationId = 'https://revelations.me/#organization';
const article = {
  id: 1,
  status: 'published',
  slug: 'ai-news',
  title: 'AI & Research <Today>',
  excerpt: 'A useful description.',
  author: 'Ada Lovelace',
  section: 'news',
  section_name: 'News',
  tags: ['AI', 'Research'],
  publication_date: '2026-07-18T10:00:00+00:00',
  modified_date: '2026-07-18T12:00:00+00:00',
  cover_image: 'https://cms.revelations.me/media/ai.png',
  cover_image_alt: 'AI cover',
};

test('normalizes canonical and image URLs without inventing invalid URLs', () => {
  assert.equal(normalizeSiteUrl(siteUrl), 'https://revelations.me');
  assert.equal(
    toAbsoluteUrl('/media/logo.png', siteUrl),
    'https://revelations.me/media/logo.png'
  );
  assert.equal(
    toAbsoluteUrl('https://cms.revelations.me/a.png', siteUrl),
    'https://cms.revelations.me/a.png'
  );
  assert.equal(toAbsoluteUrl('media/logo.png', siteUrl), '');
  assert.equal(articleCanonicalUrl(article, siteUrl), 'https://revelations.me/ai-news');
});

test('main sitemap has only canonical public URLs and honest dates', () => {
  const entries = buildMainSitemapEntries({
    articles: [article, { ...article }, { ...article, status: 'draft', slug: 'draft' }],
    siteUrl,
    staticPaths: ['', 'about'],
    sectionPaths: ['news', 'tech'],
  });
  const urls = entries.map(({ url }) => url);
  const home = entries.find(({ url }) => url === 'https://revelations.me/');
  const news = entries.find(({ url }) => url === 'https://revelations.me/news');

  assert.equal(new Set(urls).size, urls.length);
  assert.ok(urls.includes('https://revelations.me/ai-news'));
  assert.ok(!urls.includes('https://revelations.me/draft'));
  assert.equal('lastModified' in home, false);
  assert.equal(news.lastModified.toISOString(), '2026-07-18T12:00:00.000Z');
  assert.equal('lastModified' in entries.find(({ url }) => url === 'https://revelations.me/tech'), false);
});

test('news sitemap is recent-publication-only and escapes XML', () => {
  const now = new Date('2026-07-19T12:00:00+00:00');
  const xml = buildNewsSitemapXml([
    article,
    { ...article, slug: 'old-news', publication_date: '2026-07-10T10:00:00+00:00', modified_date: now.toISOString() },
    { ...article, slug: 'not-news', section: 'tech' },
  ], { siteUrl, now });

  assert.match(xml, /xmlns:news=/);
  assert.match(xml, /<news:publication_date>2026-07-18T10:00:00\+00:00<\/news:publication_date>/);
  assert.match(xml, /AI &amp; Research &lt;Today&gt;/);
  assert.doesNotMatch(xml, /old-news|not-news/);
  assert.match(buildNewsSitemapXml([], { siteUrl }), /<urlset[\s\S]*<\/urlset>/);
});

test('social metadata uses the cover image, then only a branded fallback', () => {
  const metadata = buildArticleMetadata(article, {
    siteUrl,
    fallbackImage: '/media/brand/revelations-logo.png',
  });
  const fallback = buildArticleMetadata(
    { ...article, cover_image: '', cover_image_alt: '' },
    { siteUrl, fallbackImage: '/media/brand/revelations-logo.png' }
  );

  assert.equal(metadata.openGraph.images[0].url, article.cover_image);
  assert.equal(metadata.twitter.images[0].alt, 'AI cover');
  assert.equal(
    fallback.openGraph.images[0].url,
    'https://revelations.me/media/brand/revelations-logo.png'
  );
  assert.match(metadata.alternates.canonical, /^https:\/\//);
});

test('article metadata keeps Open Graph URL equal to its canonical URL', () => {
  const metadata = buildArticleMetadata(article, {
    siteUrl,
    fallbackImage: '/media/brand/revelations-logo.png',
  });

  assert.equal(metadata.openGraph.url, metadata.alternates.canonical);
  assert.equal(
    buildPageMetadata({
      title: 'Home',
      description: 'Description',
      pathname: '/',
      siteUrl,
    }).openGraph.url,
    'https://revelations.me/'
  );
});

test('article, site graph and breadcrumbs keep images and authors truthful', () => {
  const jsonLd = buildArticleJsonLd(article, {
    siteUrl: normalizeSiteUrl(siteUrl),
    siteName: 'REVELATIONS',
    logoUrl: 'https://revelations.me/media/brand/revelations-logo.png',
    organizationId,
  });
  const withoutCoverOrAuthor = buildArticleJsonLd(
    { ...article, cover_image: '', author: '' },
    {
      siteUrl: normalizeSiteUrl(siteUrl),
      siteName: 'REVELATIONS',
      logoUrl: 'https://revelations.me/media/brand/revelations-logo.png',
      organizationId,
    }
  );
  const breadcrumb = buildArticleBreadcrumbJsonLd(article, siteUrl);
  const organization = buildOrganizationJsonLd({
    siteUrl: 'https://revelations.me',
    siteName: 'REVELATIONS',
    logoUrl: 'https://revelations.me/media/brand/revelations-logo.png',
    organizationId,
  });
  const website = buildWebsiteJsonLd({
    siteUrl: 'https://revelations.me',
    siteName: 'REVELATIONS',
    websiteId: 'https://revelations.me/#website',
    organizationId,
  });

  assert.equal(jsonLd['@type'], 'NewsArticle');
  assert.equal(
    buildArticleJsonLd(
      { ...article, section: 'tech' },
      {
        siteUrl: normalizeSiteUrl(siteUrl),
        siteName: 'REVELATIONS',
        logoUrl: 'https://revelations.me/media/brand/revelations-logo.png',
        organizationId,
      }
    )['@type'],
    'Article'
  );
  assert.equal(jsonLd.image, article.cover_image);
  assert.equal(jsonLd.author.name, 'Ada Lovelace');
  assert.equal(withoutCoverOrAuthor.image, undefined);
  assert.deepEqual(withoutCoverOrAuthor.author, { '@id': organizationId });
  assert.deepEqual(
    breadcrumb.itemListElement.map(({ name }) => name),
    ['Home', 'News', article.title]
  );
  assert.equal(buildArticleBreadcrumbJsonLd({ ...article, section: 'unknown' }, siteUrl), null);
  assert.equal(organization['@id'], organizationId);
  assert.equal(website.publisher['@id'], organizationId);
});
