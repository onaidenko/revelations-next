import { SECTIONS } from './sections.js';

const HTTP_URL = /^https?:\/\//i;

export function normalizeSiteUrl(value) {
  try {
    const url = new URL(value);

    if (url.protocol !== 'http:' && url.protocol !== 'https:') {
      return '';
    }

    return url.toString().replace(/\/+$/, '');
  } catch {
    return '';
  }
}

export function toAbsoluteUrl(value, siteUrl) {
  if (typeof value !== 'string' || !value.trim()) {
    return '';
  }

  const input = value.trim();

  if (HTTP_URL.test(input)) {
    try {
      const url = new URL(input);

      return url.protocol === 'http:' || url.protocol === 'https:'
        ? url.toString()
        : '';
    } catch {
      return '';
    }
  }

  if (!input.startsWith('/')) {
    return '';
  }

  const normalizedSiteUrl = normalizeSiteUrl(siteUrl);

  return normalizedSiteUrl
    ? `${normalizedSiteUrl}${input}`
    : '';
}

export function articleCanonicalUrl(article, siteUrl) {
  if (!article?.slug) {
    return '';
  }

  return toAbsoluteUrl(
    `/${encodeURIComponent(article.slug)}`,
    siteUrl
  );
}

function validDate(value) {
  if (!value || Number.isNaN(new Date(value).getTime())) {
    return '';
  }

  return value;
}

export function articlePublicationDate(article) {
  return validDate(
    article?.publication_date || article?.created_date
  );
}

export function articleModifiedDate(article) {
  return validDate(
    article?.modified_date ||
      article?.updated_date ||
      articlePublicationDate(article)
  );
}

export function sectionLastModified(articles, section) {
  const timestamps = articles
    .filter((article) => article.section === section)
    .map(articleModifiedDate)
    .filter(Boolean)
    .map((value) => new Date(value).getTime())
    .filter((value) => !Number.isNaN(value));

  return timestamps.length > 0
    ? new Date(Math.max(...timestamps))
    : undefined;
}

export function buildMainSitemapEntries({
  articles,
  siteUrl,
  staticPaths,
  sectionPaths,
}) {
  const urls = new Set();
  const entries = [];
  const publicArticles = articles.filter(
    (article) => article?.status === 'published' ||
      article?.status === 'publish'
  );
  const add = (entry) => {
    if (!entry.url || urls.has(entry.url)) {
      return;
    }

    urls.add(entry.url);
    entries.push(entry);
  };

  staticPaths.forEach((pathname) => {
    add({
      url: pathname === ''
        ? `${normalizeSiteUrl(siteUrl)}/`
        : toAbsoluteUrl(`/${pathname}`, siteUrl),
    });
  });

  sectionPaths.forEach((section) => {
    const lastModified = sectionLastModified(
      publicArticles,
      section
    );

    add({
      url: toAbsoluteUrl(`/${section}`, siteUrl),
      ...(lastModified ? { lastModified } : {}),
    });
  });

  publicArticles.forEach((article) => {
    const url = articleCanonicalUrl(article, siteUrl);
    const modified = articleModifiedDate(article);

    add({
      url,
      ...(modified ? { lastModified: new Date(modified) } : {}),
    });
  });

  return entries;
}

export function isRecentNewsArticle(
  article,
  now = new Date()
) {
  if (article?.section !== 'news') {
    return false;
  }

  const published = articlePublicationDate(article);
  const timestamp = new Date(published).getTime();
  const nowTimestamp = new Date(now).getTime();

  return !Number.isNaN(timestamp) &&
    !Number.isNaN(nowTimestamp) &&
    timestamp <= nowTimestamp &&
    timestamp >= nowTimestamp - 48 * 60 * 60 * 1000;
}

export function xmlEscape(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

export function buildNewsSitemapXml(
  articles,
  { siteUrl, now = new Date() } = {}
) {
  const urls = new Set();
  const entries = articles
    .filter((article) => isRecentNewsArticle(article, now))
    .map((article) => ({
      article,
      url: articleCanonicalUrl(article, siteUrl),
      publicationDate: articlePublicationDate(article),
    }))
    .filter(({ article, url, publicationDate }) =>
      Boolean(article.title && url && publicationDate)
    )
    .filter(({ url }) => {
      if (urls.has(url)) {
        return false;
      }

      urls.add(url);
      return true;
    })
    .map(({ article, url, publicationDate }) => `  <url>\n    <loc>${xmlEscape(url)}</loc>\n    <news:news>\n      <news:publication>\n        <news:name>REVELATIONS</news:name>\n        <news:language>en</news:language>\n      </news:publication>\n      <news:publication_date>${xmlEscape(publicationDate)}</news:publication_date>\n      <news:title>${xmlEscape(article.title)}</news:title>\n    </news:news>\n  </url>`);

  return `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">\n${entries.join('\n')}\n</urlset>`;
}

export function buildSocialImage(article, siteUrl, fallbackImage) {
  const coverImage = toAbsoluteUrl(article?.cover_image, siteUrl);

  if (coverImage) {
    return {
      url: coverImage,
      alt: article.cover_image_alt || article.title || 'REVELATIONS',
      isCoverImage: true,
    };
  }

  return {
    url: toAbsoluteUrl(fallbackImage, siteUrl),
    alt: 'REVELATIONS',
    isCoverImage: false,
  };
}

export function buildArticleMetadata(
  article,
  { siteUrl, fallbackImage, siteName = 'REVELATIONS' }
) {
  const canonical = articleCanonicalUrl(article, siteUrl);
  const title = article.seo_title || article.title;
  const description = article.seo_description ||
    article.excerpt ||
    `Read ${article.title} on ${siteName}.`;
  const socialImage = buildSocialImage(
    article,
    siteUrl,
    fallbackImage
  );

  return {
    title,
    description,
    alternates: {
      canonical,
    },
    openGraph: {
      type: 'article',
      title,
      description,
      url: canonical,
      images: socialImage.url
        ? [{ url: socialImage.url, alt: socialImage.alt }]
        : undefined,
      publishedTime: articlePublicationDate(article) || undefined,
      modifiedTime: articleModifiedDate(article) || undefined,
      authors: article.author ? [article.author] : undefined,
      section: article.section_name || article.section || undefined,
      tags: article.tags?.length ? article.tags : undefined,
    },
    twitter: {
      card: 'summary_large_image',
      title,
      description,
      images: socialImage.url
        ? [{ url: socialImage.url, alt: socialImage.alt }]
        : undefined,
    },
  };
}

export function buildOrganizationJsonLd({
  siteUrl,
  siteName,
  logoUrl,
  organizationId,
}) {
  return {
    '@type': 'Organization',
    '@id': organizationId,
    name: siteName,
    url: siteUrl,
    logo: {
      '@type': 'ImageObject',
      url: logoUrl,
    },
  };
}

export function buildWebsiteJsonLd({
  siteUrl,
  siteName,
  websiteId,
  organizationId,
}) {
  return {
    '@type': 'WebSite',
    '@id': websiteId,
    name: siteName,
    url: siteUrl,
    inLanguage: 'en',
    publisher: { '@id': organizationId },
  };
}

export function buildArticleJsonLd(
  article,
  {
    siteUrl,
    siteName,
    logoUrl,
    organizationId,
  }
) {
  const canonical = articleCanonicalUrl(article, siteUrl);
  const coverImage = toAbsoluteUrl(article.cover_image, siteUrl);
  const author = article.author
    ? { '@type': 'Person', name: article.author }
    : { '@id': organizationId };

  return {
    '@context': 'https://schema.org',
    '@type': article.section === 'news' ? 'NewsArticle' : 'Article',
    headline: article.title,
    description: article.excerpt || undefined,
    url: canonical,
    mainEntityOfPage: {
      '@type': 'WebPage',
      '@id': canonical,
    },
    datePublished: articlePublicationDate(article) || undefined,
    dateModified: articleModifiedDate(article) || undefined,
    inLanguage: 'en',
    isAccessibleForFree: true,
    articleSection: article.section_name || article.section || undefined,
    keywords: article.tags?.length ? article.tags : undefined,
    image: coverImage || undefined,
    author,
    publisher: {
      '@type': 'Organization',
      '@id': organizationId,
      name: siteName,
      url: siteUrl,
      logo: {
        '@type': 'ImageObject',
        url: logoUrl,
      },
    },
  };
}

export function buildArticleBreadcrumbJsonLd(article, siteUrl) {
  const section = SECTIONS[article?.section];
  const articleUrl = articleCanonicalUrl(article, siteUrl);

  if (!section || !articleUrl) {
    return null;
  }

  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      {
        '@type': 'ListItem',
        position: 1,
        name: 'Home',
        item: toAbsoluteUrl('/', siteUrl),
      },
      {
        '@type': 'ListItem',
        position: 2,
        name: article.section_name || section.title,
        item: toAbsoluteUrl(`/${article.section}`, siteUrl),
      },
      {
        '@type': 'ListItem',
        position: 3,
        name: article.title,
        item: articleUrl,
      },
    ],
  };
}

export function serializeJsonLd(value) {
  return JSON.stringify(value)
    .replace(/</g, '\\u003c')
    .replace(/>/g, '\\u003e')
    .replace(/&/g, '\\u0026')
    .replace(/\u2028/g, '\\u2028')
    .replace(/\u2029/g, '\\u2029');
}
