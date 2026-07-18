import './globals.css';
import {
  BRAND_LOGO_URL,
  DEFAULT_DESCRIPTION,
  DEFAULT_IMAGE,
  ORGANIZATION_ID,
  SITE_NAME,
  SITE_URL,
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
    default: 'REVELATIONS — People, Technology, Places & Culture',
    template: '%s — REVELATIONS',
  },
  description: DEFAULT_DESCRIPTION,
  alternates: { canonical: '/' },
  openGraph: {
    type: 'website',
    siteName: SITE_NAME,
    title: 'REVELATIONS — People, Technology, Places & Culture',
    description: DEFAULT_DESCRIPTION,
    url: '/',
    images: [DEFAULT_IMAGE],
  },
  twitter: {
    card: 'summary_large_image',
    title: 'REVELATIONS — People, Technology, Places & Culture',
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
        websiteId: WEBSITE_ID,
        organizationId: ORGANIZATION_ID,
      }),
      buildOrganizationJsonLd({
        siteUrl: SITE_URL,
        siteName: SITE_NAME,
        logoUrl: BRAND_LOGO_URL,
        organizationId: ORGANIZATION_ID,
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
