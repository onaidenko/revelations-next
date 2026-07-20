import {
  getAllTaxonomyHubs,
  getPublishedArticles,
} from '@/lib/cms-articles';
import { SITE_URL } from '@/lib/site';
import { buildMainSitemapEntries } from '@/lib/seo';

const SECTION_PATHS = [
  'news',
  'people',
  'tech',
  'places',
  'unspoken',
  'podcast',
];

const STATIC_PATHS = [
  '',
  'about',
  'advertise',
  'access',
  'contact',
  'archive',
  'topics',
];

export default async function sitemap() {
  const [articles, hubsByType] =
    await Promise.all([
      getPublishedArticles(),
      getAllTaxonomyHubs(),
    ]);

  const taxonomyHubs = Object.values(
    hubsByType
  ).flat();

  return buildMainSitemapEntries({
    articles,
    siteUrl: SITE_URL,
    staticPaths: STATIC_PATHS,
    sectionPaths: SECTION_PATHS,
    taxonomyHubs,
  });
}
