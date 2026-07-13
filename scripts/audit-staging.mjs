import fs from 'node:fs/promises';
import path from 'node:path';

const ROOT = process.cwd();
const BASE_URL = (
  process.argv[2] ||
  'https://staging.revelations.me'
).replace(/\/+$/, '');

const ARTICLES_PATH = path.join(
  ROOT,
  'data/articles.json'
);

const REPORT_PATH = path.join(
  ROOT,
  'STAGING_SEO_AUDIT.md'
);

const JSON_REPORT_PATH = path.join(
  ROOT,
  'data/staging-seo-audit.json'
);

const STATIC_PATHS = [
  '/',
  '/news',
  '/people',
  '/tech',
  '/places',
  '/unspoken',
  '/podcast',
  '/archive',
  '/about',
  '/advertise',
  '/access',
  '/contact',
];

const htmlEntities = {
  '&amp;': '&',
  '&quot;': '"',
  '&#39;': "'",
  '&lt;': '<',
  '&gt;': '>',
};

function decodeHtml(value = '') {
  return value.replace(
    /&amp;|&quot;|&#39;|&lt;|&gt;/g,
    (match) => htmlEntities[match] || match
  );
}

function extractTags(html, tagName) {
  const pattern = new RegExp(
    `<${tagName}\\b[^>]*>`,
    'gi'
  );

  return html.match(pattern) || [];
}

function getAttribute(tag, attribute) {
  const pattern = new RegExp(
    `\\b${attribute}\\s*=\\s*["']([^"']*)["']`,
    'i'
  );

  const match = tag.match(pattern);

  return match
    ? decodeHtml(match[1].trim())
    : null;
}

function getMetaContent(html, key, value) {
  const tags = extractTags(html, 'meta');

  for (const tag of tags) {
    if (
      getAttribute(tag, key)?.toLowerCase() ===
      value.toLowerCase()
    ) {
      return getAttribute(tag, 'content');
    }
  }

  return null;
}

function getCanonicalLinks(html) {
  return extractTags(html, 'link')
    .filter((tag) => {
      const rel = getAttribute(tag, 'rel') || '';

      return rel
        .toLowerCase()
        .split(/\s+/)
        .includes('canonical');
    })
    .map((tag) => getAttribute(tag, 'href'))
    .filter(Boolean);
}

function getTitle(html) {
  const match = html.match(
    /<title[^>]*>([\s\S]*?)<\/title>/i
  );

  return match
    ? decodeHtml(match[1].trim())
    : null;
}

function normalizeUrl(value, pageUrl = BASE_URL) {
  const url = new URL(value, pageUrl);

  url.hash = '';

  if (
    url.pathname.length > 1 &&
    url.pathname.endsWith('/')
  ) {
    url.pathname = url.pathname.replace(/\/+$/, '');
  }

  return url.toString();
}

function expectedUrl(pathname) {
  return normalizeUrl(
    pathname === '/'
      ? `${BASE_URL}/`
      : `${BASE_URL}${pathname}`
  );
}

function extractPageLinks(html, pageUrl) {
  const urls = new Set();

  for (const tag of extractTags(html, 'a')) {
    const href = getAttribute(tag, 'href');

    if (
      !href ||
      href.startsWith('#') ||
      href.startsWith('mailto:') ||
      href.startsWith('tel:') ||
      href.startsWith('javascript:')
    ) {
      continue;
    }

    try {
      const url = new URL(href, pageUrl);

      if (url.origin !== new URL(BASE_URL).origin) {
        continue;
      }

      url.hash = '';

      urls.add(normalizeUrl(url.toString()));
    } catch {
      // Invalid URL will be reported separately if needed.
    }
  }

  return urls;
}

function extractImages(html, pageUrl) {
  const urls = new Set();

  for (const tag of extractTags(html, 'img')) {
    const src = getAttribute(tag, 'src');

    if (!src || src.startsWith('data:')) {
      continue;
    }

    try {
      urls.add(
        normalizeUrl(
          new URL(src, pageUrl).toString()
        )
      );
    } catch {
      // Ignore malformed image here.
    }
  }

  const ogImage = getMetaContent(
    html,
    'property',
    'og:image'
  );

  if (ogImage) {
    try {
      urls.add(
        normalizeUrl(
          new URL(ogImage, pageUrl).toString()
        )
      );
    } catch {
      // Reported through missing/invalid metadata.
    }
  }

  return urls;
}

async function fetchText(url) {
  const response = await fetch(url, {
    redirect: 'follow',
    headers: {
      'user-agent':
        'REVELATIONS staging SEO audit',
      accept: 'text/html,application/xhtml+xml',
    },
  });

  const text = await response.text();

  return {
    response,
    text,
  };
}

async function checkResource(url) {
  try {
    let response = await fetch(url, {
      method: 'HEAD',
      redirect: 'follow',
      headers: {
        'user-agent':
          'REVELATIONS staging SEO audit',
      },
    });

    if (
      response.status === 405 ||
      response.status === 501
    ) {
      response = await fetch(url, {
        method: 'GET',
        redirect: 'follow',
        headers: {
          'user-agent':
            'REVELATIONS staging SEO audit',
          range: 'bytes=0-0',
        },
      });
    }

    return {
      url,
      status: response.status,
      finalUrl: response.url,
      ok:
        response.status >= 200 &&
        response.status < 400,
    };
  } catch (error) {
    return {
      url,
      status: null,
      finalUrl: null,
      ok: false,
      error: error.message,
    };
  }
}

async function mapWithConcurrency(
  items,
  concurrency,
  callback
) {
  const results = new Array(items.length);
  let currentIndex = 0;

  async function worker() {
    while (true) {
      const index = currentIndex;
      currentIndex += 1;

      if (index >= items.length) {
        return;
      }

      results[index] = await callback(
        items[index],
        index
      );
    }
  }

  await Promise.all(
    Array.from(
      {
        length: Math.min(
          concurrency,
          items.length
        ),
      },
      () => worker()
    )
  );

  return results;
}

const articles = JSON.parse(
  await fs.readFile(ARTICLES_PATH, 'utf8')
);

const articlePaths = articles
  .filter(
    (article) =>
      article.status === 'published' &&
      article.slug
  )
  .map((article) => `/${article.slug}`);

const expectedPaths = [
  ...STATIC_PATHS,
  ...articlePaths,
];

const uniqueExpectedPaths = [
  ...new Set(expectedPaths),
];

const errors = [];
const warnings = [];
const pages = [];
const allInternalLinks = new Set();
const allImages = new Set();

console.log(
  `Auditing ${uniqueExpectedPaths.length} pages`
);
console.log(`Base URL: ${BASE_URL}`);
console.log('');

await mapWithConcurrency(
  uniqueExpectedPaths,
  5,
  async (pathname, index) => {
    const pageUrl = expectedUrl(pathname);

    process.stdout.write(
      `[${String(index + 1).padStart(2, '0')}/` +
      `${uniqueExpectedPaths.length}] ${pathname}\n`
    );

    try {
      const {
        response,
        text: html,
      } = await fetchText(pageUrl);

      const pageErrors = [];
      const pageWarnings = [];

      if (response.status !== 200) {
        pageErrors.push(
          `HTTP status is ${response.status}`
        );
      }

      const contentType =
        response.headers.get('content-type') || '';

      if (!contentType.includes('text/html')) {
        pageErrors.push(
          `Unexpected Content-Type: ${contentType || 'missing'}`
        );
      }

      const xRobots =
        response.headers.get('x-robots-tag') || '';

      if (
        !xRobots.toLowerCase().includes('noindex')
      ) {
        pageErrors.push(
          'Staging X-Robots-Tag does not contain noindex'
        );
      }

      const title = getTitle(html);

      if (!title) {
        pageErrors.push('Missing title');
      } else if (title.length > 65) {
        pageWarnings.push(
          `Long title: ${title.length} characters`
        );
      }

      const description = getMetaContent(
        html,
        'name',
        'description'
      );

      if (!description) {
        pageErrors.push(
          'Missing meta description'
        );
      } else if (description.length > 170) {
        pageWarnings.push(
          `Long description: ${description.length} characters`
        );
      } else if (description.length < 50) {
        pageWarnings.push(
          `Short description: ${description.length} characters`
        );
      }

      const canonicals =
        getCanonicalLinks(html);

      if (canonicals.length === 0) {
        pageErrors.push('Missing canonical');
      } else if (canonicals.length > 1) {
        pageErrors.push(
          `Multiple canonicals: ${canonicals.length}`
        );
      } else {
        const actualCanonical = normalizeUrl(
          canonicals[0],
          pageUrl
        );

        if (actualCanonical !== pageUrl) {
          pageErrors.push(
            `Wrong canonical: ${actualCanonical}`
          );
        }
      }

      const ogTitle = getMetaContent(
        html,
        'property',
        'og:title'
      );

      const ogDescription = getMetaContent(
        html,
        'property',
        'og:description'
      );

      const ogImage = getMetaContent(
        html,
        'property',
        'og:image'
      );

      if (!ogTitle) {
        pageErrors.push('Missing og:title');
      }

      if (!ogDescription) {
        pageErrors.push(
          'Missing og:description'
        );
      }

      if (!ogImage) {
        pageErrors.push('Missing og:image');
      }

      if (
        pathname.startsWith('/') &&
        articlePaths.includes(pathname)
      ) {
        const hasArticleJsonLd =
          /<script[^>]+type=["']application\/ld\+json["'][^>]*>[\s\S]*?"@type"\s*:\s*"(?:Article|NewsArticle)"/i
            .test(html);

        if (!hasArticleJsonLd) {
          pageErrors.push(
            'Missing Article JSON-LD'
          );
        }
      }

      if (/base44/i.test(html)) {
        pageErrors.push(
          'Page HTML still contains Base44'
        );
      }

      for (
        const link of extractPageLinks(
          html,
          pageUrl
        )
      ) {
        allInternalLinks.add(link);
      }

      for (
        const image of extractImages(
          html,
          pageUrl
        )
      ) {
        allImages.add(image);
      }

      for (const message of pageErrors) {
        errors.push({
          type: 'page',
          path: pathname,
          message,
        });
      }

      for (const message of pageWarnings) {
        warnings.push({
          type: 'page',
          path: pathname,
          message,
        });
      }

      pages.push({
        path: pathname,
        url: pageUrl,
        status: response.status,
        title,
        description,
        canonical: canonicals[0] || null,
        xRobots,
        errors: pageErrors,
        warnings: pageWarnings,
      });
    } catch (error) {
      errors.push({
        type: 'page',
        path: pathname,
        message: error.message,
      });

      pages.push({
        path: pathname,
        url: pageUrl,
        status: null,
        errors: [error.message],
        warnings: [],
      });
    }
  }
);

console.log('');
console.log(
  `Checking sitemap, links and images`
);

const sitemapUrl = `${BASE_URL}/sitemap.xml`;
let sitemapLocations = [];

try {
  const {
    response,
    text,
  } = await fetchText(sitemapUrl);

  if (response.status !== 200) {
    errors.push({
      type: 'sitemap',
      path: '/sitemap.xml',
      message:
        `HTTP status is ${response.status}`,
    });
  }

  sitemapLocations = [
    ...text.matchAll(
      /<loc>([\s\S]*?)<\/loc>/gi
    ),
  ].map((match) =>
    normalizeUrl(
      decodeHtml(match[1].trim())
    )
  );

  const sitemapSet = new Set(
    sitemapLocations
  );

  for (const pathname of uniqueExpectedPaths) {
    const url = expectedUrl(pathname);

    if (!sitemapSet.has(url)) {
      errors.push({
        type: 'sitemap',
        path: pathname,
        message:
          'Expected page is missing from sitemap',
      });
    }
  }

  for (const url of sitemapLocations) {
    if (
      new URL(url).origin !==
      new URL(BASE_URL).origin
    ) {
      errors.push({
        type: 'sitemap',
        path: '/sitemap.xml',
        message:
          `Foreign sitemap URL: ${url}`,
      });
    }
  }
} catch (error) {
  errors.push({
    type: 'sitemap',
    path: '/sitemap.xml',
    message: error.message,
  });
}

const internalLinkResults =
  await mapWithConcurrency(
    [...allInternalLinks],
    8,
    checkResource
  );

for (const result of internalLinkResults) {
  if (!result.ok) {
    errors.push({
      type: 'internal-link',
      path: result.url,
      message:
        `Broken internal link: ` +
        `${result.status || result.error}`,
    });
  }
}

const imageResults =
  await mapWithConcurrency(
    [...allImages],
    6,
    checkResource
  );

for (const result of imageResults) {
  if (!result.ok) {
    errors.push({
      type: 'image',
      path: result.url,
      message:
        `Broken image: ` +
        `${result.status || result.error}`,
    });
  }
}

const report = {
  generatedAt: new Date().toISOString(),
  baseUrl: BASE_URL,
  totals: {
    expectedPages:
      uniqueExpectedPaths.length,
    checkedPages: pages.length,
    sitemapUrls:
      sitemapLocations.length,
    internalLinks:
      allInternalLinks.size,
    images: allImages.size,
    errors: errors.length,
    warnings: warnings.length,
  },
  pages,
  sitemapLocations,
  internalLinkResults,
  imageResults,
  errors,
  warnings,
};

await fs.writeFile(
  JSON_REPORT_PATH,
  `${JSON.stringify(report, null, 2)}\n`,
  'utf8'
);

const markdown = [
  '# REVELATIONS Staging SEO Audit',
  '',
  `Generated: ${report.generatedAt}`,
  '',
  `Base URL: ${BASE_URL}`,
  '',
  '## Totals',
  '',
  `- Expected pages: ${report.totals.expectedPages}`,
  `- Checked pages: ${report.totals.checkedPages}`,
  `- Sitemap URLs: ${report.totals.sitemapUrls}`,
  `- Unique internal links: ${report.totals.internalLinks}`,
  `- Unique images: ${report.totals.images}`,
  `- Errors: ${report.totals.errors}`,
  `- Warnings: ${report.totals.warnings}`,
  '',
  '## Errors',
  '',
  ...(errors.length
    ? errors.map(
        (item) =>
          `- **${item.type}** — ${item.path}: ${item.message}`
      )
    : ['- None']),
  '',
  '## Warnings',
  '',
  ...(warnings.length
    ? warnings.map(
        (item) =>
          `- **${item.type}** — ${item.path}: ${item.message}`
      )
    : ['- None']),
  '',
];

await fs.writeFile(
  REPORT_PATH,
  `${markdown.join('\n')}\n`,
  'utf8'
);

console.log('');
console.log('Staging SEO audit completed');
console.log('---------------------------');
console.log(
  `Expected pages:        ${report.totals.expectedPages}`
);
console.log(
  `Checked pages:         ${report.totals.checkedPages}`
);
console.log(
  `Sitemap URLs:          ${report.totals.sitemapUrls}`
);
console.log(
  `Unique internal links: ${report.totals.internalLinks}`
);
console.log(
  `Unique images:         ${report.totals.images}`
);
console.log(
  `Errors:                ${report.totals.errors}`
);
console.log(
  `Warnings:              ${report.totals.warnings}`
);
console.log('');
console.log('Created:');
console.log('  STAGING_SEO_AUDIT.md');
console.log('  data/staging-seo-audit.json');

if (errors.length > 0) {
  process.exitCode = 1;
}
