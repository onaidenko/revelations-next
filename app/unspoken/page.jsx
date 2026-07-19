import SectionPage from '@/components/section-page';
import { SECTIONS } from '@/lib/sections';
import { buildPageMetadata } from '@/lib/seo';
import { SITE_URL } from '@/lib/site';

const SECTION = 'unspoken';

export function generateMetadata() {
  const section = SECTIONS[SECTION];

  return buildPageMetadata({
    title: section.title,
    description: section.description,
    pathname: `/${SECTION}`,
    siteUrl: SITE_URL,
  });
}

export default function Page() {
  return <SectionPage sectionId={SECTION} />;
}
