import ArticleCard from '@/components/article-card';
import SiteFooter from '@/components/site-footer';
import SiteHeader from '@/components/site-header';
import { TAXONOMY_TYPES } from '@/lib/taxonomy-hubs';

export default function TaxonomyHubPage({ hub }) {
  const definition = TAXONOMY_TYPES[hub.type];

  return (
    <div className="min-h-screen bg-background">
      <SiteHeader />

      <main className="mx-auto max-w-7xl px-6 pb-24 pt-48 md:px-12 md:pt-56">
        <header className="mb-16 max-w-3xl">
          <span className="mb-4 block font-mono text-[11px] uppercase tracking-[0.2em] text-rose">
            {definition.singular}
          </span>

          <h1 className="mb-5 font-display text-5xl tracking-tight text-foreground md:text-7xl">
            {hub.name}
          </h1>

          <p className="font-body text-sm leading-relaxed text-muted-foreground">
            {hub.articles.length}{' '}
            {hub.articles.length === 1 ? 'story' : 'stories'}{' '}
            from REVELATIONS.
          </p>

          <div className="mt-8 h-px bg-gradient-to-r from-rose/40 via-border/30 to-transparent" />
        </header>

        <div className="grid gap-x-8 gap-y-14 md:grid-cols-2 lg:grid-cols-3">
          {hub.articles.map((article) => (
            <ArticleCard
              key={article.slug}
              article={article}
            />
          ))}
        </div>
      </main>

      <SiteFooter />
    </div>
  );
}
