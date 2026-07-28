import { NextResponse } from 'next/server';
import fallbackArticles from '@/data/articles.json';

const articles = fallbackArticles.filter(
  (article) => article?.id && article?.slug
);

const legacyIdToSlug = new Map(
  articles.map((article) => [String(article.id), article.slug])
);
const canonicalSlugs = new Set(articles.map((article) => article.slug));

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
  const slug = legacyIdToSlug.get(legacy);

  if (slug) return redirectToCanonical(request, slug);
  if (canonicalSlugs.has(legacy)) {
    return redirectToCanonical(request, legacy);
  }

  return new NextResponse(null, { status: 404 });
}

export async function GET(request, context) {
  return handleLegacyArticle(request, context);
}

export async function HEAD(request, context) {
  return handleLegacyArticle(request, context);
}
