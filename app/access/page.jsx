import SiteHeader from '@/components/site-header';
import SiteFooter from '@/components/site-footer';
import AccessRequestForm from '@/components/access-request-form';
import { buildPageMetadata } from '@/lib/seo';
import { SITE_URL } from '@/lib/site';

export const metadata = buildPageMetadata({
  title: 'Access',
  description:
    'Request selective access to the REVELATIONS Inner Circle.',
  pathname: '/access',
  siteUrl: SITE_URL,
});

export default function AccessPage() {
  return (
    <div className="min-h-screen bg-background">
      <SiteHeader />

      <main className="mx-auto max-w-2xl px-6 pb-20 pt-44 md:pt-52">
        <header>
          <span className="mb-6 block font-mono text-[11px] uppercase tracking-[0.3em] text-rose">
            Private Access
          </span>

          <h1 className="mb-4 font-display text-5xl leading-[1.1] tracking-tight text-foreground md:text-6xl">
            Request to join
            <br />
            <span className="italic">Inner Circle.</span>
          </h1>

          <p className="mb-10 font-body text-sm leading-relaxed text-muted-foreground">
            Entry is selective. Not everyone gets in. Tell us who you are and
            why you belong here.
          </p>

          <div className="mb-10 h-px bg-gradient-to-r from-rose/40 via-border/30 to-transparent" />
        </header>

        <AccessRequestForm />
      </main>

      <SiteFooter />
    </div>
  );
}
