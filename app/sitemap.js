import { getPublishedArticles } from '@/lib/articles';
import { SITE_URL } from '@/lib/site';

export default function sitemap() {
  const staticPaths = [
    '',
    'news',
    'people',
    'tech',
    'places',
    'unspoken',
    'podcast',
    'about',
    'advertise',
    'access',
    'contact',
    'archive',
  ];

  const staticPages = staticPaths.map((pathname) => ({
    url:
      pathname === ''
        ? `${SITE_URL}/`
        : `${SITE_URL}/${pathname}`,
    lastModified: new Date(),
  }));

  const articlePages = getPublishedArticles().map(
    (article) => ({
      url: `${SITE_URL}/${article.slug}`,
      lastModified: new Date(
        article.updated_date ||
          article.publication_date ||
          article.created_date
      ),
    })
  );

  return [
    ...staticPages,
    ...articlePages,
  ];
}
