import SectionPage from '@/components/section-page';
import { SECTIONS } from '@/lib/sections';

const SECTION = 'tech';

export function generateMetadata() {
  const section = SECTIONS[SECTION];

  return {
    title: section.title,
    description: section.description,
    alternates: {
      canonical: `/${SECTION}`,
    },
  };
}

export default function Page() {
  return <SectionPage sectionId={SECTION} />;
}
