import Link from 'next/link';

import { formatDate } from '@/lib/articles';
import { SECTIONS } from '@/lib/sections';

export default function SectionArticleCard({
  article,
  large = false,
}) {
  const section = SECTIONS[article.section];

  const date =
    article.publication_date || article.created_date;

  if (large) {
    return (
      <Link
        href={`/${article.slug}`}
        className="group block"
      >
        {article.cover_image && (
          <div className="mb-5 h-64 w-full overflow-hidden md:h-80">
            <img
              src={article.cover_image}
              alt={article.title}
              className="h-full w-full object-contain grayscale transition-all duration-1000 group-hover:grayscale-0"
              loading="lazy"
            />
          </div>
        )}

        <div className="mb-3 flex flex-wrap items-center gap-4">
          <span className="font-mono text-[10px] uppercase tracking-[0.2em] text-rose">
            {section?.title || article.section}
          </span>

          <span className="font-mono text-[10px] tracking-[0.1em] text-muted-foreground">
            {formatDate(date, 'short')}
          </span>
        </div>

        <h2 className="mb-4 font-display text-3xl leading-tight text-foreground transition-colors duration-500 group-hover:text-rose md:text-4xl">
          {article.title}
        </h2>

        {article.excerpt && (
          <p className="font-body text-sm leading-relaxed text-muted-foreground">
            {article.excerpt}
          </p>
        )}

        {article.author && (
          <span className="mt-4 block font-mono text-[10px] uppercase tracking-[0.1em] text-muted-foreground">
            By {article.author}
          </span>
        )}
      </Link>
    );
  }

  return (
    <Link
      href={`/${article.slug}`}
      className="group flex flex-col items-start gap-6 md:flex-row"
    >
      {article.cover_image && (
        <div className="h-36 w-full shrink-0 overflow-hidden md:h-32 md:w-48">
          <img
            src={article.cover_image}
            alt={article.title}
            className="h-full w-full object-contain grayscale transition-all duration-700 group-hover:grayscale-0"
            loading="lazy"
          />
        </div>
      )}

      <div className="min-w-0 flex-1">
        <div className="mb-2 flex flex-wrap items-center gap-4">
          <span className="font-mono text-[10px] uppercase tracking-[0.2em] text-rose">
            {section?.title || article.section}
          </span>

          <span className="font-mono text-[10px] tracking-[0.1em] text-muted-foreground">
            {formatDate(date, 'short')}
          </span>
        </div>

        <h2 className="mb-2 font-display text-xl leading-tight text-foreground transition-colors duration-500 group-hover:text-rose md:text-2xl">
          {article.title}
        </h2>

        {article.excerpt && (
          <p className="line-clamp-2 font-body text-sm leading-relaxed text-muted-foreground">
            {article.excerpt}
          </p>
        )}

        {article.author && (
          <span className="mt-2 block font-mono text-[10px] uppercase tracking-[0.1em] text-muted-foreground">
            By {article.author}
          </span>
        )}
      </div>
    </Link>
  );
}
