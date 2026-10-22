import { NextResponse } from 'next/server';

const OPENXAI_LEGACY_PATHS = new Set([
  '/how-openxai-will-change-the-worlds-perceptions',
  '/article/how-openxai-will-change-the-worlds-perceptions',
]);

const OPENXAI_CANONICAL_PATH =
  '/ashton-hettiarachi-how-openxai-will-change-the-worlds-perceptions';

export function proxy(request) {
  if (OPENXAI_LEGACY_PATHS.has(request.nextUrl.pathname)) {
    const url = request.nextUrl.clone();

    url.pathname = OPENXAI_CANONICAL_PATH;

    return NextResponse.redirect(url, 308);
  }

  if (request.nextUrl.pathname === '/Access') {
    const url = request.nextUrl.clone();

    url.pathname = '/access';

    return NextResponse.redirect(url, 308);
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    '/Access',
    '/how-openxai-will-change-the-worlds-perceptions',
    '/article/how-openxai-will-change-the-worlds-perceptions',
  ],
};
