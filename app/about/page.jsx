import SiteHeader from '@/components/site-header';
import SiteFooter from '@/components/site-footer';

export const metadata = {
  title: 'About Revelations — Future-Facing Lifestyle Media',
  description:
    'Revelations is a future-facing lifestyle media platform covering people, technology, culture, places and the unspoken forces shaping tomorrow.',
  alternates: {
    canonical: '/about',
  },
};

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
            A media for those who build the future —
            <span className="italic"> and those who see it first.</span>
          </h1>

          <p className="mb-12 max-w-2xl font-body text-lg leading-relaxed text-muted-foreground">
            We tell honest stories of founders, ideas, and the spaces where
            the future is already happening.
          </p>
        </header>

        <div className="space-y-8">
          <div className="h-px bg-gradient-to-r from-rose/40 via-border/30 to-transparent" />

          <div className="space-y-6 font-body text-base leading-[1.8] text-muted-foreground">
            <p>
              REVELATIONS is a curated media platform for founders, investors,
              and people building the future. Born as a podcast. Built as a
              media platform.
            </p>

            <p>
              We don&apos;t chase trends. We identify signals. We don&apos;t
              publish everything — only what is worth revealing. Each piece is
              intentional. Each story is selected because it reshapes how you
              think.
            </p>

            <p>
              Our editorial is organized not by topics, but by perspectives:
              signals, emergence, environments, transformations, truths,
              decisions, conversations, forms, and meaning.
            </p>
          </div>

          <div className="my-12 h-px bg-gradient-to-r from-transparent via-border/30 to-transparent" />

          <section>
            <h2 className="mb-6 font-display text-3xl text-foreground">
              Philosophy
            </h2>

            <div className="space-y-6 font-body text-base leading-[1.8] text-muted-foreground">
              <p>
                We believe the future is not something that happens to you —
                it&apos;s something built by people with taste, conviction, and
                courage. REVELATIONS serves as a filter: a curated lens through
                which you see what matters before everyone else.
              </p>

              <p>
                We are not mass media. We are not a blog. We are a selective
                editorial platform that values depth over reach, signal over
                noise, and revelation over explanation.
              </p>
            </div>
          </section>

          <div className="my-12 h-px bg-gradient-to-r from-transparent via-border/30 to-transparent" />

          <section>
            <h2 className="mb-6 font-display text-3xl text-foreground">
              Editorial Vision
            </h2>

            <div className="space-y-6 font-body text-base leading-[1.8] text-muted-foreground">
              <p>
                Frankly about tech, people, and places already living in the
                next reality. We tell honest stories of founders, ideas, and
                the spaces where the future is already happening.
              </p>

              <p className="font-display text-xl italic text-foreground">
                “Not everything is published. Only what is worth revealing.”
              </p>
            </div>
          </section>
        </div>
      </main>

      <SiteFooter />
    </div>
  );
}
