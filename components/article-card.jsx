import Link from 'next/link';

import { formatDate } from '@/lib/cms-articles';
import { SECTIONS } from '@/lib/sections';

export default function ArticleCard({
  article,
  large = false,
}) {
  const section = SECTIONS[article.section];

  return (
    <Link href={`/${article.slug}`} className="group block">
      {article.cover_image && (
        <div
          className={`${
            large ? 'h-64 md:h-80' : 'h-52'
          } mb-5 overflow-hidden`}
        >
          <img
            src={article.cover_image}
            alt={article.cover_image_alt}
            className="h-full w-full object-contain grayscale transition-all duration-700 group-hover:grayscale-0"
            loading="lazy"
          />
        </div>
      )}

      <div className="mb-3 flex items-center gap-4">
        <span className="font-mono text-[10px] uppercase tracking-[0.2em] text-rose">
          {article.section_name || section?.title || article.section}
        </span>

        <span className="font-mono text-[10px] text-muted-foreground">
          {formatDate(article.publication_date, 'short')}
        </span>
      </div>

      <h3
        className={`${
          large
            ? 'text-3xl md:text-4xl'
            : 'text-xl md:text-2xl'
        } mb-3 font-display leading-tight transition-colors group-hover:text-rose`}
      >
        {article.title}
      </h3>

      {article.excerpt && (
        <p className="line-clamp-2 font-body text-sm leading-relaxed text-muted-foreground">
          {article.excerpt}
        </p>
      )}
    </Link>
  );
}
