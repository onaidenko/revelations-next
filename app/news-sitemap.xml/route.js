import { getPublishedArticles } from '@/lib/cms-articles';
import { SITE_URL } from '@/lib/site';
import { buildNewsSitemapXml } from '@/lib/seo';

export const revalidate = 300;

export async function GET() {
  const xml = buildNewsSitemapXml(
    await getPublishedArticles(),
    { siteUrl: SITE_URL }
  );

  return new Response(xml, {
    headers: {
      'Content-Type': 'application/xml; charset=utf-8',
      'Cache-Control': 'public, s-maxage=300, stale-while-revalidate=600',
    },
  });
}
