import { getTaxonomyHubContent } from './taxonomy-hub-content.js';

const PUBLISHED_STATUSES = new Set(['published', 'publish']);

export const TAXONOMY_TYPES = {
  topics: {
    label: 'Topics',
    singular: 'Topic',
    minimumArticles: 1,
  },
  series: {
    label: 'Series',
    singular: 'Series',
    minimumArticles: 1,
  },
  locations: {
    label: 'Locations',
    singular: 'Location',
    minimumArticles: 2,
  },
  tags: {
    label: 'Tags',
    singular: 'Tag',
    minimumArticles: 2,
  },
};

function isPublished(article) {
  return PUBLISHED_STATUSES.has(article?.status);
}

function dateValue(article) {
  const value = new Date(
    article?.publication_date ||
      article?.created_date ||
      article?.updated_date ||
      0
  ).getTime();

  return Number.isNaN(value) ? 0 : value;
}

function titleFromSlug(slug) {
  return String(slug || '')
    .split('-')
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
}

function normalizeTerm(term) {
  if (!term) {
    return null;
  }

  if (typeof term === 'string') {
    const slug = term.trim();

    return slug
      ? {
          slug,
          name: titleFromSlug(slug),
        }
      : null;
  }

  const slug = String(term.slug || '').trim();
  const name = String(term.name || '').trim();

  return slug
    ? {
        slug,
        name: name || titleFromSlug(slug),
      }
    : null;
}

function uniqueTerms(terms) {
  const result = [];
  const seen = new Set();

  for (const value of terms) {
    const term = normalizeTerm(value);

    if (!term || seen.has(term.slug)) {
      continue;
    }

    seen.add(term.slug);
    result.push(term);
  }

  return result;
}

export function articleIsTopicEligible(article) {
  return article?.public_topic_eligible !== false &&
    article?.taxonomy_status !== 'needs-editorial-review';
}

export function taxonomyTermsForArticle(article, type) {
  if (!article || !TAXONOMY_TYPES[type]) {
    return [];
  }

  if (type === 'topics') {
    if (!articleIsTopicEligible(article)) {
      return [];
    }

    const values = Array.isArray(article.topic_terms)
      ? article.topic_terms
      : Array.isArray(article.topics)
        ? article.topics
        : [];

    return uniqueTerms(values);
  }

  if (type === 'series') {
    return uniqueTerms([
      article.series_term || article.series,
    ]);
  }

  if (type === 'locations') {
    const values = Array.isArray(article.location_terms)
      ? article.location_terms
      : Array.isArray(article.locations)
        ? article.locations
        : [];

    return uniqueTerms(values);
  }

  if (type === 'tags') {
    return uniqueTerms(
      Array.isArray(article.tag_terms)
        ? article.tag_terms
        : []
    );
  }

  return [];
}

export function sortHubArticles(articles) {
  return [...articles].sort(
    (first, second) =>
      dateValue(second) - dateValue(first) ||
      String(first.slug || '').localeCompare(
        String(second.slug || '')
      )
  );
}

export function buildTaxonomyHubs(articles, type) {
  const definition = TAXONOMY_TYPES[type];

  if (!definition) {
    return [];
  }

  const grouped = new Map();

  for (const article of Array.isArray(articles) ? articles : []) {
    if (!isPublished(article) || !article.slug) {
      continue;
    }

    for (const term of taxonomyTermsForArticle(article, type)) {
      const current = grouped.get(term.slug) || {
        type,
        slug: term.slug,
        name: term.name,
        pathname: `/${type}/${term.slug}`,
        articles: [],
      };

      if (!current.articles.some((item) => item.slug === article.slug)) {
        current.articles.push(article);
      }

      grouped.set(term.slug, current);
    }
  }

  return [...grouped.values()]
    .map((hub) => ({
      ...hub,
      ...(getTaxonomyHubContent(type, hub.slug) || {}),
      articles: sortHubArticles(hub.articles),
    }))
    .filter(
      (hub) =>
        hub.articles.length >= definition.minimumArticles
    )
    .sort((first, second) =>
      first.slug.localeCompare(second.slug)
    );
}

export function buildAllTaxonomyHubs(articles) {
  return Object.fromEntries(
    Object.keys(TAXONOMY_TYPES).map((type) => [
      type,
      buildTaxonomyHubs(articles, type),
    ])
  );
}

export function findTaxonomyHub(articles, type, slug) {
  if (
    typeof slug !== 'string' ||
    !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)
  ) {
    return null;
  }

  return (
    buildTaxonomyHubs(articles, type).find(
      (hub) => hub.slug === slug
    ) || null
  );
}

function hubIndex(hubs) {
  return new Map(
    (Array.isArray(hubs) ? hubs : []).map((hub) => [
      hub.slug,
      hub.pathname,
    ])
  );
}

function decorateTerms(terms, index) {
  return terms.map((term) => ({
    ...term,
    href: index.get(term.slug) || null,
  }));
}

export function buildArticleTaxonomyPresentation(
  article,
  hubsByType
) {
  const topicIndex = hubIndex(hubsByType?.topics);
  const seriesIndex = hubIndex(hubsByType?.series);
  const locationIndex = hubIndex(hubsByType?.locations);
  const tagIndex = hubIndex(hubsByType?.tags);

  const topicTerms = taxonomyTermsForArticle(article, 'topics');
  const primarySlug =
    article?.primary_topic_term?.slug ||
    article?.primary_topic ||
    '';
  const primary = topicTerms.find(
    (term) => term.slug === primarySlug
  ) || null;
  const secondary = topicTerms.filter(
    (term) => term.slug !== primarySlug
  );

  const series = taxonomyTermsForArticle(
    article,
    'series'
  );
  const locations = taxonomyTermsForArticle(
    article,
    'locations'
  );
  const tags = taxonomyTermsForArticle(article, 'tags');

  const presentation = {
    primaryTopic: primary
      ? decorateTerms([primary], topicIndex)[0]
      : null,
    secondaryTopics: decorateTerms(
      secondary,
      topicIndex
    ),
    series: decorateTerms(series, seriesIndex),
    locations: decorateTerms(
      locations,
      locationIndex
    ),
    tags: decorateTerms(tags, tagIndex),
  };

  return Object.values(presentation).some((value) =>
    Array.isArray(value) ? value.length > 0 : Boolean(value)
  )
    ? presentation
    : null;
}

export function taxonomyHubLastModified(hub) {
  const timestamps = (hub?.articles || [])
    .map((article) =>
      new Date(
        article.modified_date ||
          article.updated_date ||
          article.publication_date ||
          article.created_date ||
          0
      ).getTime()
    )
    .filter((value) => !Number.isNaN(value) && value > 0);

  return timestamps.length
    ? new Date(Math.max(...timestamps))
    : undefined;
}
