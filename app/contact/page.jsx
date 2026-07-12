import SiteHeader from '@/components/site-header';
import SiteFooter from '@/components/site-footer';

export const metadata = {
  title: 'Contact — REVELATIONS',
  description:
    'Contact REVELATIONS for editorial requests, partnerships, advertising, interviews, collaborations, and media inquiries.',
  alternates: {
    canonical: '/contact',
  },
};

export default function ContactPage() {
  return (
    <div className="min-h-screen bg-background">
      <SiteHeader />

      <main className="pb-24 pt-48 md:pt-56">
        <div className="mx-auto max-w-2xl px-6">
          <span className="mb-6 block font-mono text-[10px] uppercase tracking-[0.2em] text-rose">
            Contact
          </span>

          <h1 className="mb-10 font-display text-4xl leading-[1.1] tracking-tight text-foreground md:text-5xl">
            Contact REVELATIONS
          </h1>

          <div className="mb-10 h-px bg-gradient-to-r from-rose/40 via-border/30 to-transparent" />

          <div className="space-y-6 font-body text-base leading-relaxed text-muted-foreground">
            <p>
              REVELATIONS is a curated media platform covering people,
              technology, places, culture, and the ideas shaping what comes
              next.
            </p>

            <p>
              For editorial requests, partnerships, advertising, interviews,
              collaborations, and media inquiries, contact us at:
            </p>
          </div>

          <div className="mb-10 mt-10">
            <a
              href="mailto:info@julscorp.com"
              className="border-b border-rose/50 pb-0.5 font-mono text-sm uppercase tracking-[0.15em] text-foreground transition-colors duration-300 hover:border-rose"
            >
              info@julscorp.com
            </a>
          </div>

          <p className="font-mono text-[11px] uppercase tracking-[0.15em] text-muted-foreground/60">
            Based in Dubai. Working globally.
          </p>
        </div>
      </main>

      <SiteFooter />
    </div>
  );
}
