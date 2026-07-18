import Link from 'next/link';
import SiteHeader from '@/components/site-header';
import SiteFooter from '@/components/site-footer';
import DropsMarquee from '@/components/drops-marquee';
import { getArticlesBySection } from '@/lib/cms-articles';

function formatDate(value, variant = 'full') {
  if (!value) return '';

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '';
  }

  const options =
    variant === 'compact'
      ? { month: 'short', day: 'numeric' }
      : { month: 'short', day: 'numeric', year: 'numeric' };

  return new Intl.DateTimeFormat('en-US', options).format(date);
}

export default async function HomePage() {
  const articles = (await getArticlesBySection('news')).slice(0, 10);

  const hero = articles[0] || null;
  const secondary = articles.slice(1, 3);
  const feed = articles.slice(3);

  return (
    <div className="min-h-screen bg-background">
      <SiteHeader />

      <main className="pt-48 md:pt-56">
        {hero && (
          <section className="mx-auto max-w-7xl px-6 md:px-12">
            <div className="mb-5 flex items-center justify-between gap-6">
              <div className="flex flex-wrap items-center gap-3">
                <span className="font-mono text-[10px] uppercase tracking-[0.3em] text-rose">
                  News
                </span>

                {hero.author && (
                  <>
                    <span className="h-3 w-px bg-border/60" />
                    <span className="font-mono text-[10px] uppercase tracking-[0.12em] text-muted-foreground">
                      {hero.author}
                    </span>
                  </>
                )}

                {(hero.publication_date || hero.created_date) && (
                  <>
                    <span className="h-3 w-px bg-border/60" />
                    <span className="font-mono text-[10px] tracking-[0.1em] text-muted-foreground">
                      {formatDate(
                        hero.publication_date || hero.created_date
                      )}
                    </span>
                  </>
                )}
              </div>

              <Link
                href={`/${hero.slug}`}
                className="shrink-0 rounded-full border border-border/40 px-4 py-1.5 font-mono text-[10px] uppercase tracking-[0.2em] text-muted-foreground transition-colors duration-300 hover:border-rose/40 hover:text-foreground"
              >
                Read
              </Link>
            </div>

            <Link href={`/${hero.slug}`} className="group block">
              <h1 className="max-w-5xl font-display text-5xl leading-[1.02] tracking-tight text-foreground transition-colors duration-500 group-hover:text-rose md:text-7xl lg:text-8xl">
                {hero.title}
              </h1>

              {hero.excerpt && (
                <p className="mt-5 font-body text-base leading-relaxed text-muted-foreground">
                  {hero.excerpt}
                </p>
              )}
            </Link>

            {hero.cover_image && (
              <Link
                href={`/${hero.slug}`}
                className="group mt-8 block aspect-video w-full overflow-hidden"
              >
                <img
                  src={hero.cover_image}
                  alt={hero.cover_image_alt}
                  className="h-full w-full object-contain grayscale transition-all duration-1000 group-hover:grayscale-0"
                  loading="eager"
                />
              </Link>
            )}
          </section>
        )}

        <DropsMarquee />

        {secondary.length > 0 && (
          <section className="mx-auto max-w-7xl border-b border-border/20 px-6 py-14 md:px-12">
            <div className="grid grid-cols-1 gap-8 md:grid-cols-2 md:gap-12">
              {secondary.map((article) => (
                <Link
                  key={article.slug}
                  href={`/${article.slug}`}
                  className="group block"
                >
                  {article.cover_image && (
                    <div className="mb-5 h-52 overflow-hidden md:h-64">
                      <img
                        src={article.cover_image}
                        alt={article.cover_image_alt}
                        className="h-full w-full object-contain grayscale transition-all duration-700 group-hover:grayscale-0"
                        loading="lazy"
                      />
                    </div>
                  )}

                  <div className="mb-2 flex items-center gap-3">
                    <span className="font-mono text-[9px] uppercase tracking-[0.22em] text-rose">
                      News
                    </span>

                    <span className="font-mono text-[9px] tracking-[0.1em] text-muted-foreground">
                      {formatDate(
                        article.publication_date || article.created_date,
                        'compact'
                      )}
                    </span>
                  </div>

                  <h2 className="mb-3 font-display text-2xl leading-tight text-foreground transition-colors duration-500 group-hover:text-rose md:text-3xl">
                    {article.title}
                  </h2>

                  {article.excerpt && (
                    <p className="line-clamp-2 font-body text-sm leading-relaxed text-muted-foreground">
                      {article.excerpt}
                    </p>
                  )}
                </Link>
              ))}
            </div>
          </section>
        )}

        {feed.length > 0 && (
          <section className="mx-auto max-w-7xl px-6 py-14 md:px-12">
            <div className="mb-10 flex items-center gap-6">
              <span className="font-mono text-[10px] uppercase tracking-[0.25em] text-rose">
                Latest from News
              </span>

              <div className="h-px flex-1 bg-border/20" />

              <Link
                href="/news"
                className="font-mono text-[10px] uppercase tracking-[0.15em] text-muted-foreground transition-colors duration-300 hover:text-foreground"
              >
                View all
              </Link>
            </div>

            <div className="divide-y divide-border/20">
              {feed.map((article, index) => (
                <Link
                  key={article.slug}
                  href={`/${article.slug}`}
                  className="group flex items-start gap-6 py-8 md:gap-10"
                >
                  <span className="w-5 shrink-0 pt-1 font-mono text-[11px] text-muted-foreground/40">
                    {String(index + 4).padStart(2, '0')}
                  </span>

                  <div className="min-w-0 flex-1">
                    <div className="mb-2 flex flex-wrap items-center gap-3">
                      <span className="font-mono text-[9px] uppercase tracking-[0.2em] text-muted-foreground">
                        {formatDate(
                          article.publication_date || article.created_date
                        )}
                      </span>

                      {article.author && (
                        <>
                          <span className="h-3 w-px bg-border/40" />
                          <span className="font-mono text-[9px] uppercase tracking-[0.1em] text-muted-foreground">
                            {article.author}
                          </span>
                        </>
                      )}
                    </div>

                    <h2 className="mb-2 font-display text-xl leading-tight text-foreground transition-colors duration-500 group-hover:text-rose md:text-2xl">
                      {article.title}
                    </h2>

                    {article.excerpt && (
                      <p className="line-clamp-1 font-body text-sm leading-relaxed text-muted-foreground">
                        {article.excerpt}
                      </p>
                    )}
                  </div>

                  {article.cover_image && (
                    <div className="hidden h-20 w-28 shrink-0 overflow-hidden md:block">
                      <img
                        src={article.cover_image}
                        alt=""
                        className="h-full w-full object-contain grayscale transition-all duration-700 group-hover:grayscale-0"
                        loading="lazy"
                      />
                    </div>
                  )}
                </Link>
              ))}
            </div>
          </section>
        )}
      </main>

      <SiteFooter />
    </div>
  );
}
