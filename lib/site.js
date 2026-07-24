import { normalizeSiteUrl, toAbsoluteUrl } from '@/lib/seo';

export const SITE_NAME = 'REVELATIONS';
export const BRAND_NAME = SITE_NAME;
export const BRAND_TAGLINE =
  'Born as a podcast. Built as a media platform.';
export const BRAND_DESCRIPTOR =
  'Future-Facing Media from Dubai';

export const WEBSITE_ALTERNATE_NAMES = [
  'REVELATIONS Media',
  'revelations.me',
];

export const ORGANIZATION_ALTERNATE_NAMES = [
  'REVELATIONS Media',
];

export const SITE_URL = normalizeSiteUrl(
  process.env.NEXT_PUBLIC_SITE_URL ||
    'https://revelations.me'
);

export const DEFAULT_TITLE =
  `${BRAND_NAME} - ${BRAND_DESCRIPTOR}`;

export const DEFAULT_DESCRIPTION =
  `${BRAND_TAGLINE} ${BRAND_NAME} is a future-facing media publication from Dubai covering technology, people, places and culture. Published by JULS.`;

export const PUBLISHER_BRAND_NAME = 'JULS';
export const CONTACT_EMAIL = 'info@julscorp.com';

export const ORGANIZATION_LOCATION_NAME =
  'Dubai, United Arab Emirates';

export const SOCIAL_PROFILES = [
  {
    name: 'Instagram',
    url: 'https://www.instagram.com/revelations_me/',
  },
  {
    name: 'X',
    url: 'https://x.com/revelations_new',
  },
  {
    name: 'YouTube',
    url: 'https://www.youtube.com/@revelations_podcast',
  },
];

export const SOCIAL_PROFILE_URLS =
  SOCIAL_PROFILES.map(({ url }) => url);

export const DEFAULT_IMAGE =
  '/media/brand/revelations-logo.png';

export const BRAND_LOGO_URL = toAbsoluteUrl(
  DEFAULT_IMAGE,
  SITE_URL
);

export const ORGANIZATION_ID =
  `${SITE_URL}/#organization`;

export const WEBSITE_ID =
  `${SITE_URL}/#website`;
