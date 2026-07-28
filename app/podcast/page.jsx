import SectionPage from '@/components/section-page';
import {
  buildPageMetadata,
  buildPodcastSeriesJsonLd,
  serializeJsonLd,
} from '@/lib/seo';
import {
  ORGANIZATION_ID,
  SITE_URL,
} from '@/lib/site';

const SECTION = 'podcast';
const PODCAST_TITLE =
  'REVELATIONS Podcast - Dubai Tech & Founder Conversations';
const PODCAST_DESCRIPTION =
  'A Dubai-based podcast featuring founders, investors and builders across AI, fintech, Web3 and culture. Watch REVELATIONS episodes and explore the stories behind the future.';
const PODCAST_INTRODUCTION =
  'Based in Dubai, REVELATIONS brings together conversations with founders, investors and builders across AI, fintech, Web3, technology and culture - shared through experience rather than lectures.';

export function generateMetadata() {
  const metadata = buildPageMetadata({
    title: PODCAST_TITLE,
    description: PODCAST_DESCRIPTION,
    pathname: `/${SECTION}`,
    siteUrl: SITE_URL,
  });

  return {
    ...metadata,
    title: {
      absolute: PODCAST_TITLE,
    },
  };
}

export default function Page() {
  const podcastSeries = buildPodcastSeriesJsonLd({
    siteUrl: SITE_URL,
    organizationId: ORGANIZATION_ID,
    name: 'REVELATIONS Podcast',
    description: PODCAST_DESCRIPTION,
  });

  return (
    <>
      {podcastSeries && (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{
            __html: serializeJsonLd(podcastSeries),
          }}
        />
      )}
      <SectionPage
        sectionId={SECTION}
        introduction={PODCAST_INTRODUCTION}
      />
    </>
  );
}
