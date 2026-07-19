import {
  TaxonomyRoutePage,
  taxonomyMetadata,
  taxonomyStaticParams,
} from '@/lib/taxonomy-page';

const TYPE = 'tags';

export const dynamicParams = true;

export function generateStaticParams() {
  return taxonomyStaticParams(TYPE);
}

export function generateMetadata({ params }) {
  return taxonomyMetadata(TYPE, params);
}

export default function Page({ params }) {
  return (
    <TaxonomyRoutePage type={TYPE} params={params} />
  );
}
