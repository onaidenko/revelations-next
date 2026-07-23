import SiteHeader from '@/components/site-header';
import SiteFooter from '@/components/site-footer';
import { buildPageMetadata } from '@/lib/seo';
import {
  CONTACT_EMAIL,
  DEFAULT_DESCRIPTION,
  PUBLISHER_BRAND_NAME,
  SITE_URL,
  SOCIAL_PROFILES,
} from '@/lib/site';

export const metadata = buildPageMetadata({
  title:
    'About REVELATIONS — Dubai-Based Future-Facing Media',
  description: DEFAULT_DESCRIPTION,
  pathname: '/about',
  siteUrl: SITE_URL,
});

const EDITORIAL_SECTIONS = [
  [
    'News',
    'Signals, developments and announcements shaping what comes next.',
  ],
  [
    'People',
    'Founders, investors, creators and the decisions behind their work.',
  ],
  [
    'Tech',
    'Technology, artificial intelligence and real-world transformation.',
  ],
  [
    'Places',
    'Spaces where the future is already embedded in everyday experience.',
  ],
  [
    'Unspoken',
    'The risks, failures and uncomfortable realities hidden behind progress.',
  ],
  [
    'Podcast',
    'Conversations that reveal the experience behind technology and ambition.',
  ],
];

export default function AboutPage() {
  return (
    <div className="min-h-screen bg-background">
      <SiteHeader />

      <main className="mx-auto max-w-3xl px-6 pb-24 pt-32 md:pb-32 md:pt-44">
        <header>
          <span className="mb-8 block font-mono text-[11px] uppercase tracking-[0.2em] text-rose">
            About
          </span>

          <h1 className="mb-6 font-display text-4xl leading-[1.1] tracking-tight text-foreground md:text-6xl">
            A Dubai-based media publication for those building what comes next.
          </h1>

          <p className="mb-12 max-w-2xl font-body text-lg leading-relaxed text-muted-foreground">
            REVELATIONS is a Dubai-based future-facing media publication
            covering technology, people, places, culture and podcasts.
            Published by {PUBLISHER_BRAND_NAME}.
          </p>
        </header>

        <div className="space-y-12">
          <div className="h-px bg-gradient-to-r from-rose/40 via-border/30 to-transparent" />

          <section className="space-y-6 font-body text-base leading-[1.8] text-muted-foreground">
            <p>
              Born as a podcast and built into an editorial platform,
              REVELATIONS documents the people, technologies and environments
              already shaping the next reality.
            </p>

            <p>
              We do not publish everything. We select stories that provide
              context, reveal meaningful signals and help readers understand
              what is changing before it becomes obvious.
            </p>
          </section>

          <section>
            <h2 className="mb-6 font-display text-3xl text-foreground">
              What we cover
            </h2>

            <div className="space-y-5">
              {EDITORIAL_SECTIONS.map(
                ([name, description]) => (
                  <div
                    key={name}
                    className="border-t border-border/30 pt-5"
                  >
                    <h3 className="mb-2 font-mono text-[11px] uppercase tracking-[0.18em] text-rose">
                      {name}
                    </h3>

                    <p className="font-body text-base leading-relaxed text-muted-foreground">
                      {description}
                    </p>
                  </div>
                )
              )}
            </div>
          </section>

          <section>
            <h2 className="mb-6 font-display text-3xl text-foreground">
              Publisher
            </h2>

            <div className="space-y-5 font-body text-base leading-[1.8] text-muted-foreground">
              <p>
                REVELATIONS is published and operated under the
                {' '}
                {PUBLISHER_BRAND_NAME}
                {' '}
                brand. The publication is based in Dubai and works with
                contributors, founders and partners globally.
              </p>

              <p>
                Editorial, partnership and media inquiries:
                {' '}
                <a
                  href={`mailto:${CONTACT_EMAIL}`}
                  className="border-b border-rose/50 text-foreground"
                >
                  {CONTACT_EMAIL}
                </a>
              </p>
            </div>
          </section>

          <section>
            <h2 className="mb-6 font-display text-3xl text-foreground">
              Official channels
            </h2>

            <div className="flex flex-wrap gap-4">
              {SOCIAL_PROFILES.map(({ name, url }) => (
                <a
                  key={url}
                  href={url}
                  target="_blank"
                  rel="noreferrer"
                  className="rounded-full border border-border/40 px-4 py-2 font-mono text-[10px] uppercase tracking-[0.18em] text-muted-foreground transition-colors hover:border-rose/40 hover:text-foreground"
                >
                  {name}
                </a>
              ))}
            </div>
          </section>

          <p className="font-display text-xl italic text-foreground">
            “Not everything is published. Only what is worth revealing.”
          </p>
        </div>
      </main>

      <SiteFooter />
    </div>
  );
}
