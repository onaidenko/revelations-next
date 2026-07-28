import { NextResponse } from 'next/server';

export function proxy(request) {
  if (request.nextUrl.pathname === '/Access') {
    const url = request.nextUrl.clone();

    url.pathname = '/access';

    return NextResponse.redirect(url, 308);
  }

  return NextResponse.next();
}

export const config = {
  matcher: '/Access',
};
