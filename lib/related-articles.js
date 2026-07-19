function topics(article) {
  return Array.isArray(article?.topics)
    ? article.topics
        .map((topic) =>
          typeof topic === 'string'
            ? topic
            : topic?.slug
        )
        .filter(Boolean)
    : [];
}

function primary(article) {
  return typeof article?.primary_topic === 'string'
    ? article.primary_topic
    : article?.primary_topic?.slug || '';
}

function secondary(article) {
  const primaryTopic = primary(article);

  return topics(article).filter(
    (topic) => topic !== primaryTopic
  );
}

function series(article) {
  return typeof article?.series === 'string'
    ? article.series
    : article?.series?.slug || '';
}

function dateValue(article) {
  const value = new Date(
    article?.publication_date ||
      article?.created_date ||
      0
  ).getTime();

  return Number.isNaN(value) ? 0 : value;
}

function isPublished(article) {
  return article?.status === 'published' ||
    article?.status === 'publish';
}

export function scoreRelatedCandidate(
  source,
  candidate
) {
  if (
    !source?.slug ||
    !candidate?.slug ||
    source.slug === candidate.slug ||
    !isPublished(candidate)
  ) {
    return null;
  }

  const sourcePrimary = primary(source);
  const candidatePrimary = primary(candidate);
  const sourceSecondary = secondary(source);
  const candidateSecondary = secondary(candidate);
  const sharedSecondary = sourceSecondary.filter(
    (topic) => candidateSecondary.includes(topic)
  ).length;
  const daysApart =
    Math.abs(
      dateValue(source) - dateValue(candidate)
    ) / 86400000;

  const semantic = {
    sameSeries: Boolean(
      series(source) &&
        series(source) === series(candidate)
    ),
    samePrimary: Boolean(
      sourcePrimary &&
        sourcePrimary === candidatePrimary
    ),
    sourcePrimaryCandidateSecondary: Boolean(
      sourcePrimary &&
        candidateSecondary.includes(sourcePrimary)
    ),
    candidatePrimarySourceSecondary: Boolean(
      candidatePrimary &&
        sourceSecondary.includes(candidatePrimary)
    ),
    sharedSecondary,
    sameSection: Boolean(
      source.section &&
        source.section === candidate.section
    ),
  };

  const score =
    (semantic.sameSeries ? 1000000 : 0) +
    (semantic.samePrimary ? 100000 : 0) +
    (semantic.sourcePrimaryCandidateSecondary
      ? 10000
      : 0) +
    (semantic.candidatePrimarySourceSecondary
      ? 1000
      : 0) +
    semantic.sharedSecondary * 100 +
    (semantic.sameSection ? 10 : 0);

  return {
    score,
    daysApart,
    slug: candidate.slug,
  };
}

export function selectRelatedArticles(
  source,
  articles,
  limit = 3
) {
  const safeLimit = Math.max(
    0,
    Math.min(3, Number(limit) || 0)
  );

  if (!source?.slug || safeLimit === 0) {
    return [];
  }

  const available = (
    Array.isArray(articles) ? articles : []
  ).filter(
    (article) =>
      isPublished(article) &&
      article.slug &&
      article.slug !== source.slug
  );

  const bySlug = new Map(
    available.map((article) => [
      article.slug,
      article,
    ])
  );
  const selected = [];
  const selectedSlugs = new Set();

  const add = (article) => {
    if (
      article &&
      !selectedSlugs.has(article.slug) &&
      selected.length < safeLimit
    ) {
      selectedSlugs.add(article.slug);
      selected.push(article);
    }
  };

  const manual = Array.isArray(
    source.manual_related
  )
    ? source.manual_related
    : [];

  for (const reference of manual) {
    add(
      bySlug.get(
        typeof reference === 'string'
          ? reference
          : reference?.slug
      )
    );
  }

  const scored = available
    .filter(
      (article) => !selectedSlugs.has(article.slug)
    )
    .map((article) => ({
      article,
      rank: scoreRelatedCandidate(
        source,
        article
      ),
    }))
    .filter(({ rank }) => rank)
    .sort(
      (left, right) =>
        right.rank.score - left.rank.score ||
        left.rank.daysApart -
          right.rank.daysApart ||
        left.rank.slug.localeCompare(
          right.rank.slug
        )
    );

  for (const { article } of scored) {
    add(article);
  }

  return selected;
}
