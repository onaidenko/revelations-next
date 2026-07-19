import { notFound } from 'next/navigation';

import TaxonomyHubPage from '@/components/taxonomy-hub-page';
import {
  getTaxonomyHub,
  getTaxonomyHubs,
} from '@/lib/cms-articles';
import {
  buildTaxonomyBreadcrumbJsonLd,
  buildTaxonomyCollectionJsonLd,
  buildTaxonomyMetadata,
  serializeJsonLd,
} from '@/lib/seo';
import { SITE_NAME, SITE_URL } from '@/lib/site';

export async function taxonomyStaticParams(type) {
  return (await getTaxonomyHubs(type)).map(({ slug }) => ({
    slug,
  }));
}

export async function taxonomyMetadata(type, params) {
  const { slug } = await params;
  const hub = await getTaxonomyHub(type, slug);

  if (!hub) {
    return {
      title: 'Not found',
      robots: {
        index: false,
        follow: false,
      },
    };
  }

  return buildTaxonomyMetadata(hub, {
    siteName: SITE_NAME,
    siteUrl: SITE_URL,
  });
}

export async function TaxonomyRoutePage({
  type,
  params,
}) {
  const { slug } = await params;
  const hub = await getTaxonomyHub(type, slug);

  if (!hub) {
    notFound();
  }

  const breadcrumbs =
    buildTaxonomyBreadcrumbJsonLd(hub, SITE_URL);
  const collection = buildTaxonomyCollectionJsonLd(
    hub,
    SITE_URL
  );

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{
          __html: serializeJsonLd(breadcrumbs),
        }}
      />

      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{
          __html: serializeJsonLd(collection),
        }}
      />

      <TaxonomyHubPage hub={hub} />
    </>
  );
}
