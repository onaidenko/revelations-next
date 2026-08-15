import { NextResponse } from 'next/server';
import { getPublishedArticles } from '@/lib/cms-articles';
import { legacyArticleRedirectSlug } from '@/lib/article-catalog-policy';

function redirectToCanonical(request, slug) {
  const url = new URL(request.url);

  url.pathname = `/${slug}`;

  return new NextResponse(null, {
    status: 308,
    headers: { location: `${url.pathname}${url.search}` },
  });
}

async function handleLegacyArticle(request, { params }) {
  const { legacy } = await params;
  const slug = legacyArticleRedirectSlug(
    legacy,
    await getPublishedArticles()
  );

  if (slug) return redirectToCanonical(request, slug);

  return new NextResponse(null, { status: 404 });
}

export async function GET(request, context) {
  return handleLegacyArticle(request, context);
}

export async function HEAD(request, context) {
  return handleLegacyArticle(request, context);
}
