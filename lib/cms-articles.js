import fallbackArticles from '@/data/articles.json';
import { decodeWordPressText } from '@/lib/decode-wordpress-text';
import { fetchAllCmsArticlePages } from '@/lib/cms-pagination';
import { CMS_ARTICLES_CACHE_TAG } from '@/lib/cache-tags';
import {
  normalizeCmsTerm,
  normalizeCmsTerms,
} from '@/lib/cms-taxonomy';
import { selectRelatedArticles } from '@/lib/related-articles';
import {
  buildAllTaxonomyHubs,
  buildArticleTaxonomyPresentation,
  buildTaxonomyHubs,
  findTaxonomyHub,
} from '@/lib/taxonomy-hubs';

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

function stableArticleSort(first, second) {
  return (
    dateValue(second) - dateValue(first) ||
    String(first.slug || '').localeCompare(
      String(second.slug || '')
    )
  );
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

    primary_topic:
      typeof article.primary_topic === 'string'
        ? article.primary_topic
        : '',
    topics: Array.isArray(article.topics)
      ? article.topics
      : [],
    series:
      typeof article.series === 'string'
        ? article.series
        : '',
    locations: Array.isArray(article.locations)
      ? article.locations
      : [],
    manual_related: Array.isArray(article.manual_related)
      ? article.manual_related
      : [],

    primary_topic_term: null,
    topic_terms: [],
    series_term: null,
    location_terms: [],
    tag_terms: [],

    public_topic_eligible:
      article.public_topic_eligible !== false,
    taxonomy_status:
      article.taxonomy_status || 'proposed',
  };
}

export function normalizeCmsArticle(article) {
  const section =
    article.section?.slug ||
    article.categories?.[0]?.slug ||
    'news';

  const tagTerms = normalizeCmsTerms(article.tags);
  const topicTerms = normalizeCmsTerms(article.topics);
  const primaryTopicTerm = normalizeCmsTerm(
    article.primary_topic
  );
  const seriesTerm = normalizeCmsTerm(article.series);
  const locationTerms = normalizeCmsTerms(
    article.locations
  );

  return {
    id: article.id,
    legacy_id: article.legacy_id || '',

    slug: article.slug || '',
    status: 'published',

    title: decodeWordPressText(article.title || ''),
    excerpt: decodeWordPressText(article.excerpt || ''),
    content: article.content || '',

    author: decodeWordPressText(
      article.displayed_author || ''
    ),
    section,
    section_name: decodeWordPressText(
      article.section?.name ||
        article.categories?.[0]?.name ||
        ''
    ),

    tags: tagTerms.map((term) => term.name),
    tag_terms: tagTerms,

    primary_topic: primaryTopicTerm?.slug || '',
    primary_topic_term: primaryTopicTerm,
    topics: topicTerms.map((term) => term.slug),
    topic_terms: topicTerms,

    series: seriesTerm?.slug || '',
    series_term: seriesTerm,

    locations: locationTerms.map(
      (term) => term.slug
    ),
    location_terms: locationTerms,

    public_topic_eligible:
      article.public_topic_eligible !== false,
    taxonomy_status:
      article.taxonomy_status || 'proposed',
    manual_related: Array.isArray(
      article.manual_related
    )
      ? article.manual_related
          .map((item) => item?.slug)
          .filter(Boolean)
      : [],

    cover_image:
      article.cover_image?.url || '',
    cover_image_alt: decodeWordPressText(
      article.cover_image?.alt ||
        article.title ||
        ''
    ),

    featured: Boolean(article.featured),
    is_gated: Boolean(article.is_gated),

    youtube_url: article.youtube_url || '',

    seo_title: decodeWordPressText(
      article.seo_title || ''
    ),
    seo_description: decodeWordPressText(
      article.seo_description || ''
    ),

    publication_date:
      article.publication_date || '',

    created_date:
      article.publication_date || '',

    updated_date:
      article.modified_date ||
      article.publication_date ||
      '',

    modified_date:
      article.modified_date || '',

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

  try {
    const items = await fetchAllCmsArticlePages(
      async (page, perPage) => {
        const url = new URL(
          `${CMS_API_URL}/articles`
        );

        url.searchParams.set('page', String(page));
        url.searchParams.set(
          'per_page',
          String(perPage)
        );

        const response = await fetch(url, {
          headers: {
            Accept: 'application/json',
          },

          next: {
            revalidate: CMS_REVALIDATE_SECONDS,
            tags: [CMS_ARTICLES_CACHE_TAG],
          },

          signal: AbortSignal.timeout(5000),
        });

        if (!response.ok) {
          throw new Error(
            `CMS returned HTTP ${response.status}`
          );
        }

        return response.json();
      }
    );

    return items
      .filter(
        (article) => article?.status === 'publish'
      )
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
  ].sort(stableArticleSort);
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

  return selectRelatedArticles(
    article,
    articles,
    limit
  );
}

export async function getTaxonomyHubs(type) {
  const articles = await getPublishedArticles();

  return buildTaxonomyHubs(articles, type);
}

export async function getAllTaxonomyHubs() {
  const articles = await getPublishedArticles();

  return buildAllTaxonomyHubs(articles);
}

export async function getTaxonomyHub(
  type,
  slug
) {
  const articles = await getPublishedArticles();

  return findTaxonomyHub(
    articles,
    type,
    slug
  );
}

export async function getArticleTaxonomyPresentation(
  article
) {
  const articles = await getPublishedArticles();
  const hubs = buildAllTaxonomyHubs(articles);

  return buildArticleTaxonomyPresentation(
    article,
    hubs
  );
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
