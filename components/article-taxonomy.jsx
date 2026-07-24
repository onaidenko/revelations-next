import Link from 'next/link';

function Term({ term, primary = false }) {
  const className = primary
    ? 'font-display text-lg leading-tight text-foreground underline decoration-rose/60 decoration-1 underline-offset-8 transition-colors hover:text-rose'
    : 'font-body text-sm leading-relaxed text-muted-foreground underline decoration-border/70 underline-offset-4 transition-colors hover:decoration-rose hover:text-foreground';

  return term.href ? (
    <Link href={term.href} className={className}>
      {term.name}
    </Link>
  ) : (
    <span className={className}>{term.name}</span>
  );
}

function Group({ label, terms, primary = false }) {
  if (!terms.length) {
    return null;
  }

  return (
    <div className="grid gap-3 border-t border-border/25 py-5 sm:grid-cols-[8.5rem_minmax(0,1fr)] sm:gap-6">
      <span className="pt-1 font-mono text-[9px] uppercase tracking-[0.22em] text-muted-foreground/70">
        {label}
      </span>

      <div className="flex flex-wrap items-baseline gap-x-5 gap-y-3">
        {terms.map((term, index) => (
          <Term
            key={term.slug}
            term={term}
            primary={primary && index === 0}
          />
        ))}
      </div>
    </div>
  );
}

export default function ArticleTaxonomy({ value }) {
  if (!value) {
    return null;
  }

  const topics = [
    ...(value.primaryTopic ? [value.primaryTopic] : []),
    ...value.secondaryTopics,
  ].filter(
    (term, index, values) =>
      term &&
      values.findIndex((item) => item.slug === term.slug) === index
  );

  return (
    <aside
      aria-label="Article taxonomy"
      className="article-taxonomy mt-16 border-b border-border/25"
    >
      <Group
        label="Topics"
        terms={topics}
        primary
      />
      <Group label="Series" terms={value.series} />
      <Group label="Location" terms={value.locations} />

      <Group label="Entities" terms={value.tags} />
    </aside>
  );
}
