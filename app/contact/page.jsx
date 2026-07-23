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
  title: 'Contact REVELATIONS',
  description:
    'Contact REVELATIONS, a Dubai-based media publication published by JULS, for editorial requests, interviews, partnerships and media inquiries.',
  pathname: '/contact',
  siteUrl: SITE_URL,
});

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
              {DEFAULT_DESCRIPTION}
            </p>

            <p>
              For editorial requests, interviews, partnerships,
              advertising, collaborations and media inquiries:
            </p>
          </div>

          <div className="mb-10 mt-10">
            <a
              href={`mailto:${CONTACT_EMAIL}`}
              className="border-b border-rose/50 pb-0.5 font-mono text-sm uppercase tracking-[0.15em] text-foreground transition-colors duration-300 hover:border-rose"
            >
              {CONTACT_EMAIL}
            </a>
          </div>

          <div className="mb-10 flex flex-wrap gap-4">
            {SOCIAL_PROFILES.map(({ name, url }) => (
              <a
                key={url}
                href={url}
                target="_blank"
                rel="noreferrer"
                className="font-mono text-[10px] uppercase tracking-[0.15em] text-muted-foreground transition-colors hover:text-foreground"
              >
                {name}
              </a>
            ))}
          </div>

          <p className="font-mono text-[11px] uppercase tracking-[0.15em] text-muted-foreground/60">
            Based in Dubai · Published by {PUBLISHER_BRAND_NAME} · Working globally
          </p>
        </div>
      </main>

      <SiteFooter />
    </div>
  );
}
