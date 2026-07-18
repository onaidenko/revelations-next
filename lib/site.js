import { normalizeSiteUrl, toAbsoluteUrl } from '@/lib/seo';

export const SITE_NAME = 'REVELATIONS';
export const SITE_URL = normalizeSiteUrl(
  process.env.NEXT_PUBLIC_SITE_URL || 'https://revelations.me'
);
export const DEFAULT_DESCRIPTION = 'REVELATIONS is a future-facing media platform covering people, technology, places, culture, and the ideas shaping what comes next.';
export const DEFAULT_IMAGE = '/media/brand/revelations-logo.png';
export const BRAND_LOGO_URL = toAbsoluteUrl(
  DEFAULT_IMAGE,
  SITE_URL
);
export const ORGANIZATION_ID = `${SITE_URL}/#organization`;
export const WEBSITE_ID = `${SITE_URL}/#website`;
