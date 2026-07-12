import articles from '@/data/articles.json';

function dateValue(article) {
  return new Date(article.publication_date || article.created_date || 0).getTime();
}

export function getPublishedArticles() {
  return articles.filter((article) => article.status === 'published' && article.slug).sort((a, b) => dateValue(b) - dateValue(a));
}
export function getArticleBySlug(slug) { return getPublishedArticles().find((article) => article.slug === slug) || null; }
export function getArticlesBySection(section) { return getPublishedArticles().filter((article) => article.section === section); }
export function getRelatedArticles(article, limit = 3) {
  const all = getPublishedArticles().filter((item) => item.slug !== article.slug);
  return [...all.filter((item) => item.section === article.section), ...all.filter((item) => item.section !== article.section)].slice(0, limit);
}
export function formatDate(value, style = 'long') {
  if (!value) return '';
  return new Intl.DateTimeFormat('en', style === 'short' ? { month: 'short', day: 'numeric', year: 'numeric' } : { month: 'long', day: 'numeric', year: 'numeric' }).format(new Date(value));
}
