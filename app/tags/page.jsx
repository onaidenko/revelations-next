import TaxonomyIndexPage from '@/components/taxonomy-index-page';
import {
  getTaxonomyIndexConfig,
} from '@/lib/taxonomy-indexes';
import { buildPageMetadata } from '@/lib/seo';
import { SITE_URL } from '@/lib/site';

const config = getTaxonomyIndexConfig('tags');

export const metadata = {
  ...buildPageMetadata({
    title: config.name,
    description: config.description,
    pathname: config.pathname,
    siteUrl: SITE_URL,
  }),
  twitter: {
    card: 'summary',
    title: config.name,
    description: config.description,
  },
};

export default function EntitiesPage() {
  return <TaxonomyIndexPage type="tags" />;
}
