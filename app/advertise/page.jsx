import SiteHeader from '@/components/site-header';
import SiteFooter from '@/components/site-footer';
import { buildPageMetadata } from '@/lib/seo';
import { SITE_URL } from '@/lib/site';

const FORMATS = [
  {
    name: 'Editorial Placement',
    description:
      'Native editorial integration within our curated sections. Your story told through the REVELATIONS lens.',
  },
  {
    name: 'Podcast Feature',
    description:
      'Integrated conversations with founders and leaders. Authentic dialogue, not scripted promotion.',
  },
  {
    name: 'Artifacts Spotlight',
    description:
      'Your product presented as a cultural artifact — visual, conceptual, significant.',
  },
  {
    name: 'Inner Circle Access',
    description:
      'Exclusive positioning within our gated content. Reach the most selective audience.',
  },
];

const AUDIENCE = [
  { label: 'Founders & CEOs', value: '40%' },
  { label: 'Investors & VCs', value: '25%' },
  { label: 'Operators & Builders', value: '20%' },
  { label: 'Creative Directors', value: '15%' },
];

export const metadata = buildPageMetadata({
  title: 'Advertise — REVELATIONS',
  description:
    'Partner with REVELATIONS through editorial placements, podcast features, artifact spotlights and selected collaborations.',
  pathname: '/advertise',
  siteUrl: SITE_URL,
});

export default function AdvertisePage() {
  return (
    <div className="min-h-screen bg-background">
      <SiteHeader />

      <main className="mx-auto max-w-5xl px-6 pb-24 pt-32 md:pb-32 md:pt-44">
        <header>
          <span className="mb-8 block font-mono text-[11px] uppercase tracking-[0.2em] text-rose">
            Partner With Us
          </span>

          <h1 className="mb-6 font-display text-5xl leading-[1.1] tracking-tight text-foreground md:text-7xl">
            Reach those who
            <br />
            <span className="italic">build the future.</span>
          </h1>

          <p className="max-w-xl font-body text-lg leading-relaxed text-muted-foreground">
            REVELATIONS is not for everyone. Our audience is curated —
            founders, investors, and operators who shape industries before the
            rest notices.
          </p>
        </header>

        <div className="mt-16 h-px bg-gradient-to-r from-rose/40 via-border/30 to-transparent" />

        <section className="mt-20">
          <h2 className="mb-10 font-display text-3xl text-foreground">
            Audience
          </h2>

          <div className="grid grid-cols-2 gap-8 md:grid-cols-4">
            {AUDIENCE.map((item) => (
              <div key={item.label} className="text-center">
                <span className="mb-2 block font-display text-4xl text-rose md:text-5xl">
                  {item.value}
                </span>

                <span className="font-mono text-[10px] uppercase tracking-[0.15em] text-muted-foreground">
                  {item.label}
                </span>
              </div>
            ))}
          </div>
        </section>

        <div className="mt-20 h-px bg-gradient-to-r from-transparent via-border/30 to-transparent" />

        <section className="mt-20">
          <h2 className="mb-10 font-display text-3xl text-foreground">
            Formats
          </h2>

          <div className="grid gap-8 md:grid-cols-2">
            {FORMATS.map((format) => (
              <article
                key={format.name}
                className="border border-border/30 p-8 transition-colors duration-500 hover:border-rose/30"
              >
                <h3 className="mb-3 font-display text-xl text-foreground">
                  {format.name}
                </h3>

                <p className="font-body text-sm leading-relaxed text-muted-foreground">
                  {format.description}
                </p>
              </article>
            ))}
          </div>
        </section>

        <div className="mt-20 h-px bg-gradient-to-r from-transparent via-border/30 to-transparent" />

        <section className="mt-20 text-center">
          <h2 className="mb-4 font-display text-3xl text-foreground md:text-4xl">
            Premium, understated, effective.
          </h2>

          <p className="mb-8 font-body text-base text-muted-foreground">
            We don&apos;t sell attention. We curate alignment.
          </p>

          <a
            href="mailto:info@julscorp.com"
            className="group inline-flex items-center border border-rose/40 px-8 py-4 font-mono text-xs uppercase tracking-[0.15em] text-foreground transition-all duration-500 hover:bg-rose/10"
          >
            Get in Touch
            <span className="ml-3 text-rose">→</span>
          </a>
        </section>
      </main>

      <SiteFooter />
    </div>
  );
}
