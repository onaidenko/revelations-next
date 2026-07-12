const DROPS = [
  "AI is no longer a tool. It's becoming a layer between you and your decisions.",
  "Most AI founders don't believe in AGI. They believe in funding cycles.",
  "The real impact of AI is not automation. It's delegation of thinking.",
  "The first version barely worked. But it already replaced something important — human hesitation.",
  "You don't notice the technology here. That's how you know it's working.",
];

function MarqueeContent({ hidden = false }) {
  return (
    <span aria-hidden={hidden ? 'true' : undefined} className="inline-flex">
      {DROPS.map((drop) => (
        <span
          key={drop}
          className="mx-8 inline-flex items-center gap-6"
        >
          <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-rose" />
          <span>{drop}</span>
        </span>
      ))}
    </span>
  );
}

export default function DropsMarquee() {
  return (
    <section className="overflow-hidden border-y border-border/30 py-8">
      <div className="animate-marquee inline-flex w-max whitespace-nowrap font-mono text-[11px] uppercase tracking-[0.08em] text-rose md:text-xs">
        <MarqueeContent />
        <MarqueeContent hidden />
      </div>
    </section>
  );
}
