import fallbackArticles from '@/data/articles.json';

const CMS_API_URL = (
  process.env.REVELATIONS_CMS_API_URL || ''
).replace(/\/+$/, '');

const CMS_REVALIDATE_SECONDS = 60;

function dateValue(article) {
  const value =
    article.publication_date ||
    article.created_date ||
    article.updated_date ||
    0;

  const timestamp = new Date(value).getTime();

  return Number.isNaN(timestamp) ? 0 : timestamp;
}

function normalizeLegacyArticle(article) {
  return {
    ...article,

    legacy_id:
      article.legacy_id ||
      article.id ||
      '',

    status: 'published',
    source: 'legacy',
    content_format: 'markdown',
  };
}

function normalizeCmsArticle(article) {
  const section =
    article.section?.slug ||
    article.categories?.[0]?.slug ||
    'news';

  const tags = Array.isArray(article.tags)
    ? article.tags
        .map((tag) =>
          typeof tag === 'string'
            ? tag
            : tag?.name
        )
        .filter(Boolean)
    : [];

  return {
    id: article.id,
    legacy_id: article.legacy_id || '',

    slug: article.slug || '',
    status: 'published',

    title: article.title || '',
    excerpt: article.excerpt || '',
    content: article.content || '',

    author: article.displayed_author || '',
    section,
    tags,

    cover_image:
      article.cover_image?.url || '',

    featured: Boolean(article.featured),
    is_gated: Boolean(article.is_gated),

    youtube_url: article.youtube_url || '',

    seo_title: article.seo_title || '',
    seo_description:
      article.seo_description || '',

    publication_date:
      article.publication_date || '',

    created_date:
      article.publication_date || '',

    updated_date:
      article.modified_date ||
      article.publication_date ||
      '',

    source: 'wordpress',
    content_format: 'html',
  };
}

function getLegacyArticles() {
  return fallbackArticles
    .filter(
      (article) =>
        article.status === 'published' &&
        article.slug
    )
    .map(normalizeLegacyArticle);
}

async function fetchCmsArticles() {
  if (!CMS_API_URL) {
    return [];
  }

  const url =
    `${CMS_API_URL}/articles?per_page=100`;

  try {
    const response = await fetch(url, {
      headers: {
        Accept: 'application/json',
      },

      next: {
        revalidate: CMS_REVALIDATE_SECONDS,
      },

      signal: AbortSignal.timeout(5000),
    });

    if (!response.ok) {
      throw new Error(
        `CMS returned HTTP ${response.status}`
      );
    }

    const payload = await response.json();

    if (!Array.isArray(payload.items)) {
      throw new Error(
        'CMS response does not contain items'
      );
    }

    return payload.items
      .map(normalizeCmsArticle)
      .filter((article) => article.slug);
  } catch (error) {
    console.error(
      '[REVELATIONS CMS] Falling back to local articles:',
      error instanceof Error
        ? error.message
        : error
    );

    return [];
  }
}

function mergeArticles(
  cmsArticles,
  legacyArticles
) {
  const cmsLegacyIds = new Set(
    cmsArticles
      .map((article) =>
        String(article.legacy_id || '')
      )
      .filter(Boolean)
  );

  const cmsSlugs = new Set(
    cmsArticles
      .map((article) => article.slug)
      .filter(Boolean)
  );

  const remainingLegacyArticles =
    legacyArticles.filter((article) => {
      const legacyId = String(
        article.legacy_id ||
        article.id ||
        ''
      );

      if (
        legacyId &&
        cmsLegacyIds.has(legacyId)
      ) {
        return false;
      }

      return !cmsSlugs.has(article.slug);
    });

  return [
    ...cmsArticles,
    ...remainingLegacyArticles,
  ].sort(
    (first, second) =>
      dateValue(second) - dateValue(first)
  );
}

export async function getPublishedArticles() {
  const cmsArticles =
    await fetchCmsArticles();

  const legacyArticles =
    getLegacyArticles();

  return mergeArticles(
    cmsArticles,
    legacyArticles
  );
}

export async function getArticleBySlug(slug) {
  const articles =
    await getPublishedArticles();

  return (
    articles.find(
      (article) => article.slug === slug
    ) || null
  );
}

export async function getArticlesBySection(
  section
) {
  const articles =
    await getPublishedArticles();

  return articles.filter(
    (article) =>
      article.section === section
  );
}

export async function getRelatedArticles(
  article,
  limit = 3
) {
  const articles =
    await getPublishedArticles();

  const otherArticles = articles.filter(
    (item) => item.slug !== article.slug
  );

  return [
    ...otherArticles.filter(
      (item) =>
        item.section === article.section
    ),

    ...otherArticles.filter(
      (item) =>
        item.section !== article.section
    ),
  ].slice(0, limit);
}

export function formatDate(
  value,
  style = 'long'
) {
  if (!value) {
    return '';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '';
  }

  const options =
    style === 'short'
      ? {
          month: 'short',
          day: 'numeric',
          year: 'numeric',
        }
      : {
          month: 'long',
          day: 'numeric',
          year: 'numeric',
        };

  return new Intl.DateTimeFormat(
    'en',
    options
  ).format(date);
}
