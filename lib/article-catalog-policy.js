export function selectLiveArticleCatalog({
  cmsConfigured,
  cmsArticles,
  legacyArticles,
}) {
  return cmsConfigured ? cmsArticles : legacyArticles;
}

export function legacyArticleRedirectSlug(
  legacy,
  publishedArticles
) {
  const legacyIdToSlug = new Map();
  const canonicalSlugs = new Set();

  for (const article of publishedArticles) {
    const slug = String(article?.slug || '');

    if (!slug) continue;

    canonicalSlugs.add(slug);

    const legacyId = String(article?.legacy_id || '');

    if (legacyId) {
      legacyIdToSlug.set(legacyId, slug);
    }
  }

  if (legacyIdToSlug.has(legacy)) {
    return legacyIdToSlug.get(legacy);
  }

  return canonicalSlugs.has(legacy)
    ? legacy
    : null;
}
