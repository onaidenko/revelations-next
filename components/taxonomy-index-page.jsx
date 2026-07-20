import Link from 'next/link';

import SiteFooter from '@/components/site-footer';
import SiteHeader from '@/components/site-header';
import { getTaxonomyHubs } from '@/lib/cms-articles';
import {
  buildTaxonomyIndexBreadcrumbJsonLd,
  buildTaxonomyIndexCollectionJsonLd,
  serializeJsonLd,
} from '@/lib/seo';
import { SITE_URL } from '@/lib/site';
import {
  getTaxonomyIndexConfig,
} from '@/lib/taxonomy-indexes';

export default async function TaxonomyIndexPage({ type }) {
  const config = getTaxonomyIndexConfig(type);
  const hubs = await getTaxonomyHubs(type);
  const breadcrumbs =
    buildTaxonomyIndexBreadcrumbJsonLd({
      name: config.name,
      pathname: config.pathname,
      siteUrl: SITE_URL,
    });
  const collection =
    buildTaxonomyIndexCollectionJsonLd({
      name: config.name,
      description: config.description,
      pathname: config.pathname,
      hubs,
      siteUrl: SITE_URL,
    });

  return (
    <div className="min-h-screen bg-background">
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

      <SiteHeader />

      <main className="mx-auto max-w-7xl px-6 pb-24 pt-48 md:px-12 md:pt-56">
        <header className="mb-16 max-w-3xl">
          <span className="mb-4 block font-mono text-[11px] uppercase tracking-[0.2em] text-rose">
            Explore
          </span>

          <h1 className="mb-5 font-display text-5xl tracking-tight text-foreground md:text-7xl">
            {config.name}
          </h1>

          <p className="font-body text-base leading-relaxed text-muted-foreground">
            {config.description}
          </p>

          <p className="mt-5 font-mono text-[10px] uppercase tracking-[0.16em] text-muted-foreground/60">
            {hubs.length}{' '}
            {hubs.length === 1
              ? config.collectionSingular
              : config.collectionPlural}{' '}
            currently available
          </p>

          <div className="mt-8 h-px bg-gradient-to-r from-rose/40 via-border/30 to-transparent" />
        </header>

        {hubs.length === 0 ? (
          <div className="py-20 text-center">
            <p className="font-mono text-xs uppercase tracking-[0.15em] text-muted-foreground">
              {config.emptyMessage}
            </p>
          </div>
        ) : (
          <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {hubs.map((hub) => (
              <Link
                key={hub.slug}
                href={hub.pathname}
                className="group flex min-h-64 flex-col border border-border/30 p-7 transition-colors duration-300 hover:border-rose/50"
              >
                <div className="mb-8 flex items-center justify-between gap-4">
                  <span className="font-mono text-[9px] uppercase tracking-[0.2em] text-rose">
                    {config.singular}
                  </span>

                  <span className="font-mono text-[9px] uppercase tracking-[0.12em] text-muted-foreground/60">
                    {hub.articles.length}{' '}
                    {hub.articles.length === 1
                      ? 'story'
                      : 'stories'}
                  </span>
                </div>

                <h2 className="mb-4 font-display text-2xl leading-tight text-foreground transition-colors duration-300 group-hover:text-rose">
                  {hub.name}
                </h2>

                <p className="mb-8 font-body text-sm leading-relaxed text-muted-foreground">
                  {hub.seoDescription ||
                    hub.introduction ||
                    `Explore REVELATIONS stories connected to ${hub.name}.`}
                </p>

                <span className="mt-auto font-mono text-[9px] uppercase tracking-[0.18em] text-muted-foreground transition-colors duration-300 group-hover:text-foreground">
                  {config.actionLabel} →
                </span>
              </Link>
            ))}
          </div>
        )}
      </main>

      <SiteFooter />
    </div>
  );
}
