import { NextResponse } from 'next/server';
import fallbackArticles from '@/data/articles.json';

const LEGACY_ARTICLE_ROUTES = new Map(
  fallbackArticles
    .filter((article) => article?.id && article?.slug)
    .map((article) => [String(article.id), article.slug])
);

function notFound() {
  return new NextResponse('Not found', {
    status: 404,
    headers: {
      'content-type': 'text/plain; charset=utf-8',
    },
  });
}

export function proxy(request) {
  const articleMatch = request.nextUrl.pathname.match(
    /^\/article\/([^/]+)$/
  );

  if (articleMatch) {
    const legacyId = articleMatch[1];
    const slug = LEGACY_ARTICLE_ROUTES.get(legacyId);

    if (slug) {
      const url = request.nextUrl.clone();

      url.pathname = `/${slug}`;

      return NextResponse.redirect(url, 308);
    }

    if (/^\d+$/.test(legacyId) || /^[a-f0-9]{24}$/i.test(legacyId)) {
      return notFound();
    }
  }

  if (request.nextUrl.pathname === '/Access') {
    const url = request.nextUrl.clone();

    url.pathname = '/access';

    return NextResponse.redirect(url, 308);
  }

  return NextResponse.next();
}

export const config = {
  matcher: ['/Access', '/article/:path*'],
};
