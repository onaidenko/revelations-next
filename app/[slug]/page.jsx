import { notFound } from 'next/navigation';
import Link from 'next/link';

import SiteHeader from '@/components/site-header';
import SiteFooter from '@/components/site-footer';
import ArticleCard from '@/components/article-card';
import ArticleBody from '@/components/article-body';
import ArticleTaxonomy from '@/components/article-taxonomy';
import ShareButton from '@/components/share-button';
import YouTubeEmbed from '@/components/youtube-embed';
import {
  buildArticleHeaderLabels,
  formatReadingTime,
} from '@/lib/article-presentation';

import {
  getArticleBySlug,
  getArticleTaxonomyPresentation,
  getPublishedArticles,
  getRelatedArticles,
  formatDate,
  formatArticleAuthors,
} from '@/lib/cms-articles';

import { SECTIONS } from '@/lib/sections';
import {
  BRAND_LOGO_URL,
  DEFAULT_IMAGE,
  ORGANIZATION_ID,
  SITE_NAME,
  SITE_URL,
} from '@/lib/site';
import {
  buildArticleBreadcrumbJsonLd,
  buildArticleJsonLd,
  buildArticleMetadata,
  buildPodcastEpisodeJsonLd,
  serializeJsonLd,
} from '@/lib/seo';

export async function generateStaticParams() {
  return (await getPublishedArticles()).map(
    ({ slug }) => ({ slug })
  );
}

export async function generateMetadata({ params }) {
  const { slug } = await params;
  const article = await getArticleBySlug(slug);

  if (!article) {
    return {
      title: 'Not found',
      robots: {
        index: false,
        follow: false,
      },
    };
  }

  return buildArticleMetadata(article, {
    siteUrl: SITE_URL,
    fallbackImage: DEFAULT_IMAGE,
    siteName: SITE_NAME,
  });
}

export default async function ArticlePage({
  params,
}) {
  const { slug } = await params;
  const article = await getArticleBySlug(slug);

  if (!article) {
    notFound();
  }

  const section = SECTIONS[article.section];
  const [related, taxonomy] = await Promise.all([
    getRelatedArticles(article),
    getArticleTaxonomyPresentation(article),
  ]);
  const canonical = `${SITE_URL}/${article.slug}`;

  const publicationDate =
    article.publication_date ||
    article.created_date;
  const articleJsonLd = buildArticleJsonLd(article, {
    siteUrl: SITE_URL,
    siteName: SITE_NAME,
    logoUrl: BRAND_LOGO_URL,
    organizationId: ORGANIZATION_ID,
  });
  const podcastEpisodeJsonLd = buildPodcastEpisodeJsonLd(
    article,
    {
      siteUrl: SITE_URL,
      seriesId: `${SITE_URL}/podcast#podcast-series`,
    }
  );
  const jsonLd = podcastEpisodeJsonLd || articleJsonLd;
  const breadcrumbs =
    buildArticleBreadcrumbJsonLd(
      article,
      SITE_URL
    );
  const headerLabels = buildArticleHeaderLabels({
    section: article.section,
    sectionName:
      article.section_name || section?.title || article.section,
    taxonomy,
  });
  const readingTime = formatReadingTime(article.content);
  const byline = formatArticleAuthors(article);
  const publicAuthors = article.author_profiles?.filter((author) => author.is_public_profile) || [];

  return (
    <div className="min-h-screen bg-background">
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{
          __html: serializeJsonLd(jsonLd),
        }}
      />

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
        <article className="pb-12 pt-48 md:pt-56">
          <header className="mx-auto max-w-5xl px-6 md:px-10">
            {headerLabels.length > 0 && (
              <div
                aria-label="Article topics"
                className="mb-6 font-mono text-[10px] uppercase leading-relaxed tracking-[0.2em] text-rose"
              >
                {headerLabels.map((label, index) => (
                  <span key={`${label.name}-${label.href || index}`}>
                    {index > 0 && (
                      <span aria-hidden="true" className="mx-3 text-muted-foreground">·</span>
                    )}
                    {label.href ? (
                      <Link href={label.href} className="break-words transition-colors hover:text-foreground">
                        {label.name}
                      </Link>
                    ) : (
                      <span className="break-words">{label.name}</span>
                    )}
                  </span>
                ))}
              </div>
            )}

            <h1 className="mb-7 font-display text-4xl leading-[1.05] tracking-tight text-foreground md:text-6xl lg:text-7xl">
              {article.title}
            </h1>

            {article.excerpt && (
              <p className="mb-9 font-body text-lg leading-relaxed text-muted-foreground md:text-xl">
                {article.excerpt}
              </p>
            )}

            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between md:gap-8">
              <div className="flex flex-wrap items-center gap-x-3 gap-y-2 font-mono text-[11px] uppercase tracking-[0.15em] text-muted-foreground">
                {byline && <span>By {article.author_profiles?.length ? article.author_profiles.map((author, index) => (<span key={author.slug}>{index > 0 && (index === article.author_profiles.length - 1 ? ' and ' : ', ')}{author.is_public_profile ? <Link href={`/authors/${author.slug}`} className="transition-colors hover:text-foreground">{author.name}</Link> : author.name}</span>)) : byline}</span>}
                {byline && publicationDate && <span aria-hidden="true">·</span>}
                {publicationDate && (
                  <time dateTime={publicationDate}>
                    {formatDate(publicationDate)}
                  </time>
                )}
                {readingTime && publicationDate && <span aria-hidden="true">·</span>}
                {readingTime && <span>{readingTime}</span>}
              </div>

              <ShareButton url={canonical} />
            </div>

            <div className="mt-8 h-px bg-gradient-to-r from-rose/40 via-border/30 to-transparent" />
          </header>

          {article.cover_image && (
            <div className="mx-auto mt-14 max-w-[88rem] px-6 md:px-10">
              <img
                src={article.cover_image}
                alt={article.cover_image_alt}
                className="h-[42vh] w-full object-contain md:h-[68vh]"
                loading="eager"
                fetchPriority="high"
              />
            </div>
          )}

          {article.revelation && (
            <section className="mx-auto mt-16 max-w-5xl px-6 md:mt-20 md:px-10" aria-labelledby="article-revelation">
              <div className="border-l-2 border-rose/60 pl-6 md:pl-8">
                <h2 id="article-revelation" className="mb-5 font-mono text-[10px] uppercase tracking-[0.25em] text-rose">
                  THE REVELATION
                </h2>
                <p className="font-display text-2xl leading-snug text-foreground md:text-3xl lg:text-4xl">
                  {article.revelation}
                </p>
              </div>
            </section>
          )}

          <div className="mx-auto max-w-5xl px-6 py-14 md:px-10 md:py-20">

            <ArticleBody
              content={article.content}
              format={article.content_format}
            />

            {article.youtube_url && (
              <YouTubeEmbed
                url={article.youtube_url}
              />
            )}

            {(article.public_sources.length > 0 || article.source_note || article.editorial_note || article.disclosure) && (
              <aside className="mt-12 border-t border-border/30 pt-8 font-body text-sm leading-relaxed text-muted-foreground" aria-label="Article editorial context">
                {article.public_sources.length > 0 && (
                  <section className="mb-6" aria-labelledby="article-public-sources">
                    <h2 id="article-public-sources" className="mb-3 font-mono text-[10px] uppercase tracking-[0.2em] text-rose">PUBLIC SOURCES</h2>
                    <ol className="space-y-2 pl-5">
                      {article.public_sources.map((source) => (
                        <li key={`${source.label}-${source.url}`}>
                          <a className="underline decoration-rose/50 underline-offset-4 transition-colors hover:text-foreground" href={source.url} target="_blank" rel="noreferrer">{source.label}</a>
                        </li>
                      ))}
                    </ol>
                  </section>
                )}
                {article.source_note && <section className="mb-6"><h2 className="mb-2 font-mono text-[10px] uppercase tracking-[0.2em] text-rose">SOURCE NOTE</h2><p>{article.source_note}</p></section>}
                {article.editorial_note && <section className="mb-6"><h2 className="mb-2 font-mono text-[10px] uppercase tracking-[0.2em] text-rose">EDITORIAL NOTE</h2><p>{article.editorial_note}</p></section>}
            {article.disclosure && <section className="border-l-2 border-rose/60 pl-4"><h2 className="mb-2 font-mono text-[10px] uppercase tracking-[0.2em] text-foreground">DISCLOSURE</h2><p>{article.disclosure}</p></section>}
              </aside>
            )}

            {publicAuthors.length > 0 && (
              <section className="mt-20 border-t border-border/35 pt-8" aria-labelledby="about-author">
                <h2 id="about-author" className="mb-8 font-mono text-[10px] uppercase tracking-[0.22em] text-rose">ABOUT THE AUTHOR</h2>
                <div className="space-y-10">{publicAuthors.map((author) => (
                  <div key={author.slug} className={`grid gap-5 sm:gap-8 ${author.image?.url ? 'sm:grid-cols-[7rem_minmax(0,1fr)]' : ''}`}>
                    {author.image?.url && <img src={author.image.url} alt={author.image.alt || author.name} className="h-24 w-24 rounded-full object-cover sm:h-28 sm:w-28" />}
                    <div><h3 className="font-display text-3xl leading-tight text-foreground">{author.name}</h3>{author.role && <p className="mt-2 font-mono text-[10px] uppercase tracking-[0.15em] text-rose">{author.role}</p>}{author.bio && <p className="mt-5 max-w-3xl font-body text-base leading-relaxed text-muted-foreground">{author.bio}</p>}<div className="mt-6 flex flex-wrap gap-x-5 gap-y-3 font-mono text-[10px] uppercase tracking-[0.15em] text-rose"><Link href={`/authors/${author.slug}`} className="transition-colors hover:text-foreground">Profile</Link>{author.same_as?.map((url) => url.includes('linkedin.com') ? <a key={url} href={url} target="_blank" rel="noreferrer" className="transition-colors hover:text-foreground">LinkedIn</a> : null)}<Link href={`/authors/${author.slug}`} className="transition-colors hover:text-foreground">All stories</Link></div></div>
                  </div>
                ))}</div>
              </section>
            )}

            <ArticleTaxonomy value={taxonomy} />
          </div>
        </article>
      </main>

      {related.length > 0 && (
        <section className="mx-auto max-w-7xl border-t border-border/20 px-6 pb-24 pt-16 md:px-12">
          <div className="mb-10 flex items-center gap-6">
            <h2 className="font-mono text-[10px] uppercase tracking-[0.25em] text-rose">
              Related
            </h2>

            <div className="h-px flex-1 bg-border/20" />
          </div>

          <div className="grid gap-8 md:grid-cols-3">
            {related.map((relatedArticle) => (
              <ArticleCard
                key={relatedArticle.slug}
                article={relatedArticle}
              />
            ))}
          </div>
        </section>
      )}

      <SiteFooter />
    </div>
  );
}
