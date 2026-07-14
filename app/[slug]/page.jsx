import { notFound } from 'next/navigation';
import Link from 'next/link';

import SiteHeader from '@/components/site-header';
import SiteFooter from '@/components/site-footer';
import ArticleCard from '@/components/article-card';
import ArticleBody from '@/components/article-body';
import ShareButton from '@/components/share-button';

import {
  getArticleBySlug,
  getPublishedArticles,
  getRelatedArticles,
  formatDate,
} from '@/lib/cms-articles';

import { SECTIONS } from '@/lib/sections';
import {
  DEFAULT_IMAGE,
  SITE_NAME,
  SITE_URL,
} from '@/lib/site';

export async function generateStaticParams() {
  return (await getPublishedArticles()).map(({ slug }) => ({ slug }));
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

  const seoTitle =
    article.seo_title || article.title;

  const seoDescription =
    article.seo_description ||
    article.excerpt ||
    `Read ${article.title} on REVELATIONS.`;

  const image = article.cover_image || DEFAULT_IMAGE;

  return {
    title: seoTitle,
    description: seoDescription,

    alternates: {
      canonical: `/${article.slug}`,
    },

    openGraph: {
      type: 'article',
      title: seoTitle,
      description: seoDescription,
      url: `/${article.slug}`,
      images: [image],
      publishedTime: article.publication_date,
      modifiedTime: article.updated_date,
      authors: article.author ? [article.author] : undefined,
      section: article.section,
      tags: article.tags,
    },

    twitter: {
      card: 'summary_large_image',
      title: seoTitle,
      description: seoDescription,
      images: [image],
    },
  };
}

export default async function ArticlePage({ params }) {
  const { slug } = await params;
  const article = await getArticleBySlug(slug);

  if (!article) {
    notFound();
  }

  const section = SECTIONS[article.section];
  const related = await getRelatedArticles(article);
  const canonical = `${SITE_URL}/${article.slug}`;

  const publicationDate =
    article.publication_date || article.created_date;

  const modifiedDate =
    article.updated_date || publicationDate;

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type':
      article.section === 'news'
        ? 'NewsArticle'
        : 'Article',

    headline: article.title,
    description: article.excerpt || undefined,
    image: article.cover_image || DEFAULT_IMAGE,
    datePublished: publicationDate,
    dateModified: modifiedDate,

    author: {
      '@type': 'Person',
      name: article.author || SITE_NAME,
    },

    publisher: {
      '@type': 'Organization',
      name: SITE_NAME,
      url: SITE_URL,
    },

    mainEntityOfPage: {
      '@type': 'WebPage',
      '@id': canonical,
    },

    articleSection: article.section,
    keywords: article.tags,
  };

  return (
    <div className="min-h-screen bg-background">
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{
          __html: JSON.stringify(jsonLd),
        }}
      />

      <SiteHeader />

      <article className="pb-12 pt-48 md:pt-56">
        {article.cover_image && (
          <div className="mx-auto mb-12 max-w-6xl px-6 md:px-12">
            <img
              src={article.cover_image}
              alt={article.title}
              className="h-[40vh] w-full object-contain md:h-[60vh]"
              loading="eager"
              fetchPriority="high"
            />
          </div>
        )}

        <header className="mx-auto max-w-3xl px-6">
          <div className="mb-6 flex flex-wrap items-center gap-4">
            <Link
              href={`/${article.section}`}
              className="font-mono text-[10px] uppercase tracking-[0.2em] text-rose transition-colors hover:text-foreground"
            >
              {section?.title || article.section}
            </Link>

            <span className="h-3 w-px bg-border" />

            <span className="font-mono text-[10px] tracking-[0.1em] text-muted-foreground">
              {formatDate(publicationDate)}
            </span>
          </div>

          <h1 className="mb-6 font-display text-4xl leading-[1.1] tracking-tight text-foreground md:text-5xl lg:text-6xl">
            {article.title}
          </h1>

          {article.excerpt && (
            <p className="mb-8 font-body text-lg leading-relaxed text-muted-foreground">
              {article.excerpt}
            </p>
          )}

          <div className="flex flex-wrap items-center justify-between gap-4">
            {article.author && (
              <span className="font-mono text-[11px] uppercase tracking-[0.15em] text-muted-foreground">
                By {article.author}
              </span>
            )}

            <ShareButton url={canonical} />
          </div>

          <div className="mt-8 h-px bg-gradient-to-r from-rose/40 via-border/30 to-transparent" />
        </header>

        <div className="mx-auto max-w-3xl px-6 py-12">
          <ArticleBody content={article.content} format={article.content_format} />

          {article.youtube_url && (
            <div className="mt-12">
              <span className="mb-4 block font-mono text-[10px] uppercase tracking-[0.2em] text-rose">
                Watch
              </span>

              <a
                href={article.youtube_url}
                target="_blank"
                rel="noopener noreferrer"
                className="group inline-flex items-center font-mono text-xs uppercase tracking-[0.15em] text-foreground"
              >
                <span className="relative">
                  Watch on YouTube
                  <span className="absolute -bottom-1 left-0 h-px w-full origin-left scale-x-0 bg-rose transition-transform duration-700 group-hover:scale-x-100" />
                </span>

                <span className="ml-3 text-rose">→</span>
              </a>
            </div>
          )}

          {Array.isArray(article.tags) &&
            article.tags.length > 0 && (
              <div className="mt-12 border-t border-border/30 pt-8">
                <div className="flex flex-wrap gap-3">
                  {article.tags.map((tag) => (
                    <span
                      key={tag}
                      className="border border-border/40 px-3 py-1 font-mono text-[10px] uppercase tracking-[0.1em] text-muted-foreground"
                    >
                      {tag}
                    </span>
                  ))}
                </div>
              </div>
            )}
        </div>
      </article>

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
