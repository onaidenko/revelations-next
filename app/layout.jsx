import './globals.css';
import { DEFAULT_DESCRIPTION, DEFAULT_IMAGE, SITE_NAME, SITE_URL } from '@/lib/site';

export const metadata = {
  metadataBase: new URL(SITE_URL),
  title: { default: 'REVELATIONS — People, Technology, Places & Culture', template: '%s — REVELATIONS' },
  description: DEFAULT_DESCRIPTION,
  alternates: { canonical: '/' },
  openGraph: { type: 'website', siteName: SITE_NAME, title: 'REVELATIONS — People, Technology, Places & Culture', description: DEFAULT_DESCRIPTION, url: '/', images: [DEFAULT_IMAGE] },
  twitter: { card: 'summary_large_image', title: 'REVELATIONS — People, Technology, Places & Culture', description: DEFAULT_DESCRIPTION, images: [DEFAULT_IMAGE] },
};

export default function RootLayout({ children }) {
  return <html lang="en" className="dark" suppressHydrationWarning><body>{children}</body></html>;
}
