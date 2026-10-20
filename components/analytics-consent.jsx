'use client';

import { useEffect, useState, useSyncExternalStore } from 'react';
import Link from 'next/link';
import Script from 'next/script';
import {
  ANALYTICS_CONSENT,
  GA4_MEASUREMENT_ID,
  clearAnalyticsCookies,
  disableAnalytics,
  enableAnalytics,
  isAnalyticsEnabled,
  readAnalyticsConsent,
  saveAnalyticsConsent,
} from '@/lib/analytics-consent';

const buttonClassName =
  'border border-border/60 px-4 py-2 font-mono text-[10px] uppercase tracking-[0.16em] text-foreground transition-colors hover:border-rose hover:text-rose focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-rose';
const CONSENT_CHANGE_EVENT = 'revelations:analytics-consent-change';
const analyticsEnabled = isAnalyticsEnabled();

function subscribeToConsent(callback) {
  window.addEventListener('storage', callback);
  window.addEventListener(CONSENT_CHANGE_EVENT, callback);

  return () => {
    window.removeEventListener('storage', callback);
    window.removeEventListener(CONSENT_CHANGE_EVENT, callback);
  };
}

function getClientConsent() {
  return readAnalyticsConsent(window.localStorage);
}

function getServerConsent() {
  return undefined;
}

function notifyConsentChange() {
  window.dispatchEvent(new Event(CONSENT_CHANGE_EVENT));
}

export default function AnalyticsConsent() {
  const consent = useSyncExternalStore(
    subscribeToConsent,
    getClientConsent,
    getServerConsent
  );
  const [showPreferences, setShowPreferences] = useState(false);

  useEffect(() => {
    if (consent === ANALYTICS_CONSENT.ACCEPTED) {
      enableAnalytics(window);
    } else {
      disableAnalytics(window);
    }
  }, [consent]);

  const acceptAnalytics = () => {
    enableAnalytics(window);
    saveAnalyticsConsent(window.localStorage, ANALYTICS_CONSENT.ACCEPTED);
    notifyConsentChange();
    setShowPreferences(false);
  };

  const rejectAnalytics = () => {
    disableAnalytics(window);
    clearAnalyticsCookies(document);
    saveAnalyticsConsent(window.localStorage, ANALYTICS_CONSENT.REJECTED);
    notifyConsentChange();
    setShowPreferences(false);
  };

  const showBanner =
    consent !== undefined &&
    (consent === null || showPreferences);

  return (
    <>
      {analyticsEnabled && consent === ANALYTICS_CONSENT.ACCEPTED && (
        <Script
          id="ga4-tag"
          src={`https://www.googletagmanager.com/gtag/js?id=${GA4_MEASUREMENT_ID}`}
          strategy="afterInteractive"
        />
      )}

      {showBanner && (
        <section
          aria-labelledby="analytics-consent-title"
          className="fixed bottom-4 left-4 right-4 z-50 mx-auto max-w-xl border border-border/70 bg-background/95 p-4 shadow-2xl backdrop-blur md:bottom-6 md:left-6 md:right-auto"
          role="dialog"
        >
          <p
            id="analytics-consent-title"
            className="font-mono text-[10px] uppercase tracking-[0.18em] text-rose"
          >
            Optional analytics
          </p>
          <p className="mt-2 max-w-md font-body text-sm leading-relaxed text-muted-foreground">
            Analytics help us understand how REVELATIONS is used. Read the{' '}
            <Link
              href="/cookie-policy"
              className="text-foreground underline decoration-rose/70 underline-offset-4 hover:decoration-rose"
            >
              Cookie Policy
            </Link>{' '}
            or change your choice at any time.
          </p>
          <div className="mt-4 flex flex-wrap gap-2">
            <button className={buttonClassName} onClick={acceptAnalytics} type="button">
              Accept analytics
            </button>
            <button className={buttonClassName} onClick={rejectAnalytics} type="button">
              Reject
            </button>
          </div>
        </section>
      )}

      {consent !== undefined && !showBanner && (
        <button
          aria-label="Change analytics consent"
          className="fixed bottom-4 left-4 z-40 border border-border/50 bg-background/90 px-3 py-2 font-mono text-[9px] uppercase tracking-[0.16em] text-muted-foreground transition-colors hover:border-rose hover:text-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-rose"
          onClick={() => setShowPreferences(true)}
          type="button"
        >
          Cookie settings
        </button>
      )}
    </>
  );
}
