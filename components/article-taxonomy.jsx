import Link from 'next/link';

function Term({ term, primary = false }) {
  const className = primary
    ? 'border border-rose/50 px-3 py-1 font-mono text-[10px] uppercase tracking-[0.12em] text-rose transition-colors hover:border-rose hover:text-foreground'
    : 'border border-border/40 px-3 py-1 font-mono text-[10px] uppercase tracking-[0.1em] text-muted-foreground transition-colors hover:border-rose/40 hover:text-foreground';

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
    <div className="flex flex-col gap-3 sm:flex-row sm:items-start">
      <span className="w-28 shrink-0 pt-1 font-mono text-[9px] uppercase tracking-[0.2em] text-muted-foreground/60">
        {label}
      </span>

      <div className="flex flex-wrap gap-2">
        {terms.map((term) => (
          <Term
            key={term.slug}
            term={term}
            primary={primary}
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

  return (
    <aside
      aria-label="Article taxonomy"
      className="mt-12 space-y-4 border-t border-border/30 pt-8"
    >
      <Group
        label="Primary topic"
        terms={
          value.primaryTopic ? [value.primaryTopic] : []
        }
        primary
      />

      <Group
        label="More topics"
        terms={value.secondaryTopics}
      />

      <Group label="Series" terms={value.series} />

      <Group
        label="Location"
        terms={value.locations}
      />

      <Group label="Entities" terms={value.tags} />
    </aside>
  );
}
