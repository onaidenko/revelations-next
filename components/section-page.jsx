import { notFound } from 'next/navigation';
import Link from 'next/link';

import SiteHeader from '@/components/site-header';
import SiteFooter from '@/components/site-footer';
import SectionArticleCard from '@/components/section-article-card';

import { getArticlesBySection } from '@/lib/cms-articles';
import { SECTIONS } from '@/lib/sections';
import { BRAND_NAME, SITE_URL } from '@/lib/site';
import {
  buildSectionBreadcrumbJsonLd,
  serializeJsonLd,
} from '@/lib/seo';

const STICKY_RIGHT = new Set([
  'news',
  'tech',
  'unspoken',
]);

export default async function SectionPage({
  sectionId,
  introduction,
}) {
  const section = SECTIONS[sectionId];

  if (!section) {
    notFound();
  }

  const articles = await getArticlesBySection(sectionId);
  const breadcrumbs = buildSectionBreadcrumbJsonLd(
    { ...section, id: sectionId },
    SITE_URL
  );
  const isPodcast = sectionId === 'podcast';
  const stickyRight = STICKY_RIGHT.has(sectionId);

  const featured = articles[0] || null;

  const sticky = isPodcast
    ? articles[1] || null
    : articles[0] || null;

  const feed = isPodcast
    ? articles.slice(2)
    : articles.slice(1);

  return (
    <div className="min-h-screen bg-background">
      {breadcrumbs && (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{
            __html: serializeJsonLd(breadcrumbs),
          }}
        />
      )}
      <SiteHeader />

      <main>
        <section className="mx-auto max-w-7xl px-6 pb-12 pt-48 md:px-12 md:pb-16 md:pt-56">
          <span className="mb-4 block font-mono text-[11px] uppercase tracking-[0.2em] text-rose">
            {section.subtitle}
          </span>

          <h1 className="mb-4 font-display text-5xl tracking-tight text-foreground md:text-7xl lg:text-8xl">
            {isPodcast ? `${BRAND_NAME} Podcast` : section.title}
          </h1>

          <p className="max-w-xl font-body text-base leading-relaxed text-muted-foreground">
            {section.description}
          </p>

          {introduction && (
            <p className="mt-4 max-w-xl font-body text-base leading-relaxed text-muted-foreground">
              {introduction}
            </p>
          )}

          <div className="mt-10 h-px bg-gradient-to-r from-rose/40 via-border/30 to-transparent" />
        </section>

        {articles.length === 0 ? (
          <section className="px-6 py-20 text-center">
            <p className="font-mono text-xs uppercase tracking-[0.15em] text-muted-foreground">
              Content is being curated
            </p>

            <p className="mt-3 font-body text-sm text-muted-foreground">
              Not everything is published. Only what is worth revealing.
            </p>
          </section>
        ) : (
          <section className="mx-auto max-w-7xl px-6 pb-24 md:px-12">
            {isPodcast && featured && (
              <div className="mb-16">
                <Link
                  href={`/${featured.slug}`}
                  className="group relative block overflow-hidden"
                >
                  {featured.cover_image ? (
                    <>
                      <div className="relative hidden h-[55vh] min-h-[380px] overflow-hidden md:block">
                        <img
                          src={featured.cover_image}
                          alt={featured.cover_image_alt}
                          className="h-full w-full object-contain grayscale transition-all duration-1000 group-hover:grayscale-0"
                        />

                        <div className="absolute inset-0 bg-gradient-to-t from-background via-background/30 to-transparent" />

                        <div className="absolute inset-x-0 bottom-0 p-8 md:p-12">
                          <span className="mb-3 block font-mono text-[10px] uppercase tracking-[0.2em] text-rose">
                            Latest Episode
                          </span>

                          <h2 className="mb-3 max-w-3xl font-display text-3xl leading-tight text-foreground md:text-5xl">
                            {featured.title}
                          </h2>

                          {featured.excerpt && (
                            <p className="max-w-xl font-body text-sm leading-relaxed text-muted-foreground">
                              {featured.excerpt}
                            </p>
                          )}
                        </div>
                      </div>

                      <div className="md:hidden">
                        <div className="mb-4 overflow-hidden">
                          <img
                            src={featured.cover_image}
                            alt={featured.cover_image_alt}
                            className="w-full object-contain grayscale transition-all duration-1000 group-hover:grayscale-0"
                          />
                        </div>

                        <div className="px-1 pb-4">
                          <span className="mb-2 block font-mono text-[10px] uppercase tracking-[0.2em] text-rose">
                            Latest Episode
                          </span>

                          <h2 className="mb-2 font-display text-2xl leading-tight text-foreground">
                            {featured.title}
                          </h2>

                          {featured.excerpt && (
                            <p className="line-clamp-2 font-body text-sm leading-relaxed text-muted-foreground">
                              {featured.excerpt}
                            </p>
                          )}
                        </div>
                      </div>
                    </>
                  ) : (
                    <div className="border border-border/30 p-10 md:p-16">
                      <span className="mb-4 block font-mono text-[10px] uppercase tracking-[0.2em] text-rose">
                        Latest Episode
                      </span>

                      <h2 className="mb-4 font-display text-4xl text-foreground md:text-5xl">
                        {featured.title}
                      </h2>

                      {featured.excerpt && (
                        <p className="max-w-xl font-body text-sm text-muted-foreground">
                          {featured.excerpt}
                        </p>
                      )}
                    </div>
                  )}
                </Link>

                <div className="mt-12 h-px bg-gradient-to-r from-transparent via-border/30 to-transparent" />
              </div>
            )}

            {(sticky || feed.length > 0) && (
              <div
                className={`flex flex-col gap-0 md:flex-row ${
                  stickyRight ? 'md:flex-row-reverse' : ''
                }`}
              >
                {sticky && (
                  <div
                    className={`w-full pb-16 md:sticky md:top-44 md:w-2/5 md:self-start md:pb-0 ${
                      stickyRight
                        ? 'md:pl-12'
                        : 'md:pr-12'
                    }`}
                  >
                    <span className="mb-6 block font-mono text-[9px] uppercase tracking-[0.2em] text-muted-foreground">
                      {isPodcast
                        ? 'Previous Episode'
                        : 'Featured'}
                    </span>

                    <SectionArticleCard
                      article={sticky}
                      large
                    />
                  </div>
                )}

                {sticky && feed.length > 0 && (
                  <div className="hidden w-px self-stretch bg-border/20 md:block" />
                )}

                {feed.length > 0 && (
                  <div
                    className={`w-full md:flex-1 ${
                      sticky
                        ? stickyRight
                          ? 'md:pr-12'
                          : 'md:pl-12'
                        : ''
                    }`}
                  >
                    <div className="divide-y divide-border/20">
                      {feed.map((article) => (
                        <div
                          key={article.slug}
                          className="py-8 first:pt-0"
                        >
                          <SectionArticleCard article={article} />
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            )}
          </section>
        )}
      </main>

      <SiteFooter />
    </div>
  );
}
