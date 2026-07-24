import './globals.css';
import {
  BRAND_LOGO_URL,
  BRAND_TAGLINE,
  CONTACT_EMAIL,
  DEFAULT_DESCRIPTION,
  DEFAULT_IMAGE,
  DEFAULT_TITLE,
  ORGANIZATION_ID,
  ORGANIZATION_LOCATION_NAME,
  ORGANIZATION_ALTERNATE_NAMES,
  PUBLISHER_BRAND_NAME,
  SITE_NAME,
  SITE_URL,
  SOCIAL_PROFILE_URLS,
  WEBSITE_ALTERNATE_NAMES,
  WEBSITE_ID,
} from '@/lib/site';
import {
  buildOrganizationJsonLd,
  buildWebsiteJsonLd,
  serializeJsonLd,
} from '@/lib/seo';

export const metadata = {
  metadataBase: new URL(SITE_URL),
  title: {
    default: DEFAULT_TITLE,
    template: '%s - REVELATIONS',
  },
  description: DEFAULT_DESCRIPTION,
  alternates: { canonical: SITE_URL },
  openGraph: {
    type: 'website',
    siteName: SITE_NAME,
    title: DEFAULT_TITLE,
    description: DEFAULT_DESCRIPTION,
    url: SITE_URL,
    images: [DEFAULT_IMAGE],
  },
  twitter: {
    card: 'summary_large_image',
    title: DEFAULT_TITLE,
    description: DEFAULT_DESCRIPTION,
    images: [DEFAULT_IMAGE],
  },
};

export default function RootLayout({ children }) {
  const siteGraph = {
    '@context': 'https://schema.org',
    '@graph': [
      buildWebsiteJsonLd({
        siteUrl: SITE_URL,
        siteName: SITE_NAME,
        alternateNames:
          WEBSITE_ALTERNATE_NAMES,
        description:
          DEFAULT_DESCRIPTION,
        websiteId: WEBSITE_ID,
        organizationId: ORGANIZATION_ID,
      }),
      buildOrganizationJsonLd({
        siteUrl: SITE_URL,
        siteName: SITE_NAME,
        alternateNames:
          ORGANIZATION_ALTERNATE_NAMES,
        description:
          DEFAULT_DESCRIPTION,
        slogan: BRAND_TAGLINE,
        logoUrl: BRAND_LOGO_URL,
        organizationId:
          ORGANIZATION_ID,
        sameAs:
          SOCIAL_PROFILE_URLS,
        contactEmail:
          CONTACT_EMAIL,
        locationName:
          ORGANIZATION_LOCATION_NAME,
        publisherBrandName:
          PUBLISHER_BRAND_NAME,
      }),
    ],
  };

  return (
    <html lang="en" className="dark" suppressHydrationWarning>
      <body>
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{
            __html: serializeJsonLd(siteGraph),
          }}
        />
        {children}
      </body>
    </html>
  );
}
