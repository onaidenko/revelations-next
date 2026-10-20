import Link from 'next/link';

import LegalPage from '@/components/legal-page';
import { buildPageMetadata } from '@/lib/seo';
import { SITE_URL } from '@/lib/site';

export const metadata = buildPageMetadata({
  title: 'Privacy Policy',
  description:
    'How REVELATIONS handles website information, optional analytics and public contact requests.',
  pathname: '/privacy-policy',
  siteUrl: SITE_URL,
});

export default function PrivacyPolicyPage() {
  return (
    <LegalPage title="Privacy Policy">
      <p>
        Last updated: October 20, 2026. This Privacy Policy describes the
        public REVELATIONS website at revelations.me. It should be read with
        the <Link href="/cookie-policy" className="text-foreground underline decoration-rose/70 underline-offset-4 hover:decoration-rose">Cookie Policy</Link>.
      </p>

      <section>
        <h2 className="mb-3 font-display text-2xl text-foreground">Who is responsible</h2>
        <p>
          The following controller details have not yet been confirmed for
          publication:
        </p>
        <ul className="mt-3 list-disc space-y-2 pl-5">
          <li>Data controller: [legal entity name to be confirmed]</li>
          <li>Registered address: [registered address to be confirmed]</li>
          <li>Privacy contact: [privacy contact email to be confirmed]</li>
        </ul>
        <p className="mt-3">
          General contact details are available on the <Link href="/contact" className="text-foreground underline decoration-rose/70 underline-offset-4 hover:decoration-rose">Contact page</Link>. The controller details above must be confirmed before this policy is relied on in production.
        </p>
      </section>

      <section>
        <h2 className="mb-3 font-display text-2xl text-foreground">Information connected with website use</h2>
        <p>
          REVELATIONS publishes editorial content and does not provide public
          user accounts or comments in the current frontend. Information may
          be processed when you browse the website, choose optional analytics,
          use third-party content or send an email request.
        </p>
        <ul className="mt-3 list-disc space-y-2 pl-5">
          <li>
            Google Analytics 4 is available only after you choose Accept
            analytics. It measures website interactions, including page views,
            scroll interactions and outbound-link interactions.
          </li>
          <li>
            The Access form prepares a message in your email application with
            the fields you enter. The public frontend does not submit those
            fields to its own form endpoint. If you send the message, your
            email provider and the recipient process its contents.
          </li>
          <li>
            The Contact page provides an email link. Information you choose to
            send by email is processed through the relevant email services.
          </li>
        </ul>
      </section>

      <section>
        <h2 className="mb-3 font-display text-2xl text-foreground">Optional analytics</h2>
        <p>
          The site uses Google Analytics 4 with Basic Consent Mode v2. Google
          Analytics is not loaded before an explicit analytics choice. If you
          accept, the site grants analytics storage only. Advertising storage,
          advertising user data and advertising personalization remain denied.
        </p>
        <p className="mt-3">
          You can reject analytics or withdraw a previous choice at any time
          with Cookie settings. Withdrawing consent disables further analytics
          collection by the site and clears accessible Google Analytics cookies.
        </p>
      </section>

      <section>
        <h2 className="mb-3 font-display text-2xl text-foreground">Third-party content and links</h2>
        <p>
          Some pages can load media or resources from other providers. YouTube
          thumbnails load from YouTube&apos;s image service, and a
          privacy-enhanced YouTube player loads only after you choose Play.
          Article and profile images can be served from the CMS or another
          source selected for that content. Social profiles and other external
          links open services operated by their respective providers.
        </p>
        <p className="mt-3">
          These providers may process technical information such as your IP
          address and browser details when their resource is requested. Their
          processing is governed by their own policies.
        </p>
      </section>

      <section>
        <h2 className="mb-3 font-display text-2xl text-foreground">Sharing, retention and transfers</h2>
        <p>
          The confirmed recipients described above are Google Analytics after
          analytics consent, email providers when you choose to send email,
          and third-party media or link providers when their resources are
          requested. Hosting, server-log, processor, international-transfer
          and retention arrangements have not yet been confirmed for
          publication.
        </p>
      </section>

      <section>
        <h2 className="mb-3 font-display text-2xl text-foreground">Your choices and rights</h2>
        <p>
          Depending on where you live and the applicable law, you may have
          rights concerning personal information, including access, correction,
          deletion, restriction, objection or withdrawal of consent. Cookie
          settings controls optional analytics directly. A confirmed privacy
          contact and applicable request process are required before the site
          can provide a complete rights-request route.
        </p>
      </section>

      <section>
        <h2 className="mb-3 font-display text-2xl text-foreground">Policy updates</h2>
        <p>
          This page will be updated when the website&apos;s data practices or the
          confirmed legal information changes. The current version date appears
          at the top of this page.
        </p>
      </section>
    </LegalPage>
  );
}
