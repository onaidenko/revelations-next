import SiteFooter from '@/components/site-footer';
import SiteHeader from '@/components/site-header';

export default function LegalPage({ title, children }) {
  return (
    <div className="min-h-screen bg-background">
      <SiteHeader />

      <main className="mx-auto max-w-3xl px-6 pb-24 pt-48 md:pt-56">
        <span className="mb-6 block font-mono text-[10px] uppercase tracking-[0.2em] text-rose">
          Legal
        </span>
        <h1 className="mb-6 font-display text-4xl leading-[1.1] tracking-tight text-foreground md:text-6xl">
          {title}
        </h1>
        <div className="mb-12 h-px bg-gradient-to-r from-rose/40 via-border/30 to-transparent" />

        <article className="space-y-10 font-body text-base leading-relaxed text-muted-foreground">
          {children}
        </article>
      </main>

      <SiteFooter />
    </div>
  );
}
