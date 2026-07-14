import Link from 'next/link';

import SiteHeader from '@/components/site-header';
import SiteFooter from '@/components/site-footer';

import {
  formatDate,
  getPublishedArticles,
} from '@/lib/cms-articles';

const SECTION_ORDER = [
  'news',
  'people',
  'tech',
  'places',
  'unspoken',
  'podcast',
];

const SECTION_LABELS = {
  news: 'News',
  people: 'People',
  tech: 'Tech',
  places: 'Places',
  unspoken: 'Unspoken',
  podcast: 'Podcast',
};

export const metadata = {
  title: 'Archive',
  description:
    'Every published REVELATIONS story across people, technology, culture, places and conversations.',
  alternates: {
    canonical: '/archive',
  },
};

export default async function ArchivePage() {
  const articles = await getPublishedArticles();

  const grouped = articles.reduce((result, article) => {
    const section = article.section || 'news';

    if (!result[section]) {
      result[section] = [];
    }

    result[section].push(article);

    return result;
  }, {});

  const populatedSections = SECTION_ORDER.filter(
    (sectionId) => grouped[sectionId]?.length > 0
  );

  return (
    <div className="min-h-screen bg-background">
      <SiteHeader />

      <main className="mx-auto max-w-5xl px-6 pb-24 pt-48 md:px-12 md:pt-56">
        <header className="mb-16">
          <span className="mb-4 block font-mono text-[11px] uppercase tracking-[0.2em] text-rose">
            Archive
          </span>

          <h1 className="mb-4 font-display text-5xl tracking-tight text-foreground md:text-7xl">
            All Stories
          </h1>

          <p className="max-w-xl font-body text-sm leading-relaxed text-muted-foreground">
            Every published piece — across people, technology, culture,
            places, unspoken narratives, and conversations.
          </p>

          <div className="mt-8 h-px bg-gradient-to-r from-rose/40 via-border/30 to-transparent" />
        </header>

        {populatedSections.length === 0 ? (
          <div className="py-20 text-center">
            <p className="font-mono text-xs uppercase tracking-[0.15em] text-muted-foreground">
              Content is being curated
            </p>
          </div>
        ) : (
          <div className="space-y-16">
            {populatedSections.map((sectionId) => {
              const sectionArticles = grouped[sectionId];

              return (
                <section key={sectionId}>
                  <div className="mb-6 flex items-center gap-4">
                    <Link
                      href={`/${sectionId}`}
                      className="font-mono text-[10px] uppercase tracking-[0.25em] text-rose transition-colors duration-300 hover:text-foreground"
                    >
                      {SECTION_LABELS[sectionId]}
                    </Link>

                    <div className="h-px flex-1 bg-border/20" />

                    <span className="font-mono text-[10px] tracking-[0.1em] text-muted-foreground/50">
                      {sectionArticles.length}
                    </span>
                  </div>

                  <div className="divide-y divide-border/20">
                    {sectionArticles.map((article) => {
                      const date =
                        article.publication_date ||
                        article.created_date;

                      return (
                        <article
                          key={article.slug}
                          className="py-5 first:pt-0"
                        >
                          <Link
                            href={`/${article.slug}`}
                            className="group flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                          >
                            <div className="min-w-0 flex-1">
                              <h2 className="mb-1 font-display text-lg leading-snug text-foreground transition-colors duration-300 group-hover:text-rose">
                                {article.title}
                              </h2>

                              {article.excerpt && (
                                <p className="line-clamp-2 font-body text-sm leading-relaxed text-muted-foreground">
                                  {article.excerpt}
                                </p>
                              )}
                            </div>

                            <span className="shrink-0 whitespace-nowrap font-mono text-[10px] tracking-[0.1em] text-muted-foreground/60 sm:ml-8 sm:mt-1">
                              {formatDate(date, 'short')}
                            </span>
                          </Link>
                        </article>
                      );
                    })}
                  </div>
                </section>
              );
            })}
          </div>
        )}
      </main>

      <SiteFooter />
    </div>
  );
}
