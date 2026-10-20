import Link from 'next/link';

import LegalPage from '@/components/legal-page';
import { buildPageMetadata } from '@/lib/seo';
import { SITE_URL } from '@/lib/site';

export const metadata = buildPageMetadata({
  title: 'Cookie Policy',
  description:
    'How REVELATIONS uses optional analytics cookies and browser storage.',
  pathname: '/cookie-policy',
  siteUrl: SITE_URL,
});

export default function CookiePolicyPage() {
  return (
    <LegalPage title="Cookie Policy">
      <p>
        Last updated: October 20, 2026. This Cookie Policy explains the
        cookies and browser storage used by the public REVELATIONS website at
        revelations.me. See also the <Link href="/privacy-policy" className="text-foreground underline decoration-rose/70 underline-offset-4 hover:decoration-rose">Privacy Policy</Link>.
      </p>

      <section>
        <h2 className="mb-3 font-display text-2xl text-foreground">How analytics consent works</h2>
        <p>
          REVELATIONS uses Google Analytics 4 only after you choose Accept
          analytics in the consent banner or Cookie settings. The site uses
          Basic Consent Mode v2, which means the Google Analytics tag is not
          loaded before that choice. Rejecting analytics prevents the tag from
          loading.
        </p>
        <p className="mt-3">
          Accepting analytics grants analytics storage only. The site does not
          grant advertising storage, advertising user data or advertising
          personalization through this control.
        </p>
      </section>

      <section>
        <h2 className="mb-3 font-display text-2xl text-foreground">Cookies and browser storage in use</h2>
        <div className="space-y-5">
          <div className="border-l border-rose/50 pl-4">
            <h3 className="font-mono text-[11px] uppercase tracking-[0.16em] text-foreground">Analytics preference</h3>
            <p className="mt-2">
              `revelations.analytics-consent` is first-party localStorage, not
              a cookie. It stores whether you accepted or rejected optional
              analytics so the site can respect the choice on later visits. It
              remains until you change the choice in Cookie settings or clear
              browser storage.
            </p>
          </div>
          <div className="border-l border-rose/50 pl-4">
            <h3 className="font-mono text-[11px] uppercase tracking-[0.16em] text-foreground">Theme preference</h3>
            <p className="mt-2">
              `revelations-theme` is first-party localStorage used to remember
              the selected light or dark theme. It remains until you change the
              setting or clear browser storage.
            </p>
          </div>
          <div className="border-l border-rose/50 pl-4">
            <h3 className="font-mono text-[11px] uppercase tracking-[0.16em] text-foreground">Google Analytics</h3>
            <p className="mt-2">
              Google Analytics may set cookies with names such as `_ga` and
              `_ga_*` only after analytics consent. They support aggregated
              measurement of how the site is used. Their duration is controlled
              by the Google Analytics configuration and must be confirmed
              before production publication.
            </p>
          </div>
          <div className="border-l border-rose/50 pl-4">
            <h3 className="font-mono text-[11px] uppercase tracking-[0.16em] text-foreground">YouTube</h3>
            <p className="mt-2">
              A privacy-enhanced YouTube player from youtube-nocookie.com is
              requested only when you choose Play. YouTube may then use its own
              cookies or similar storage under its own policies.
            </p>
          </div>
        </div>
      </section>

      <section>
        <h2 className="mb-3 font-display text-2xl text-foreground">Changing your choice</h2>
        <p>
          Select Cookie settings at the lower left of the page to accept or
          reject analytics later. Rejecting or withdrawing analytics consent
          disables future collection by the site and clears accessible `_ga*`
          cookies. You can also use your browser controls to clear cookies and
          localStorage.
        </p>
      </section>

      <section>
        <h2 className="mb-3 font-display text-2xl text-foreground">Third-party resources</h2>
        <p>
          YouTube thumbnails, the YouTube player, article images from the CMS
          or their source host, and external links can connect your browser to
          third parties. Their cookies and similar technologies are controlled
          by those providers. This policy does not govern third-party sites
          opened through external links.
        </p>
      </section>

      <section>
        <h2 className="mb-3 font-display text-2xl text-foreground">Information still to be confirmed</h2>
        <p>
          The data controller&apos;s legal name, registered address, privacy
          contact, hosting and server-log arrangements, Google Analytics
          retention settings, international-transfer arrangements and any
          jurisdiction-specific legal basis must be confirmed before production
          release. These details belong in the <Link href="/privacy-policy" className="text-foreground underline decoration-rose/70 underline-offset-4 hover:decoration-rose">Privacy Policy</Link> once approved.
        </p>
      </section>
    </LegalPage>
  );
}
