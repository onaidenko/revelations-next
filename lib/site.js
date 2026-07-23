import { normalizeSiteUrl, toAbsoluteUrl } from '@/lib/seo';

export const SITE_NAME = 'REVELATIONS';

export const SITE_ALTERNATE_NAMES = [
  'Revelations Media',
  'revelations.me',
];

export const SITE_URL = normalizeSiteUrl(
  process.env.NEXT_PUBLIC_SITE_URL ||
    'https://revelations.me'
);

export const DEFAULT_TITLE =
  'REVELATIONS — Future-Facing Media from Dubai';

export const DEFAULT_DESCRIPTION =
  'REVELATIONS is a Dubai-based future-facing media publication covering technology, people, places, culture and podcasts. Published by JULS.';

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
