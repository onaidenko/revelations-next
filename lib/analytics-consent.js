export const GA4_MEASUREMENT_ID =
  process.env.NEXT_PUBLIC_GA4_MEASUREMENT_ID?.trim() || null;
export const ANALYTICS_CONSENT_STORAGE_KEY = 'revelations.analytics-consent';

export const ANALYTICS_CONSENT = {
  ACCEPTED: 'accepted',
  REJECTED: 'rejected',
};

const DENIED_CONSENT = {
  ad_storage: 'denied',
  ad_user_data: 'denied',
  ad_personalization: 'denied',
  analytics_storage: 'denied',
};

const ACCEPTED_ANALYTICS_CONSENT = {
  ...DENIED_CONSENT,
  analytics_storage: 'granted',
};

export function isAnalyticsEnabled(measurementId = GA4_MEASUREMENT_ID) {
  return typeof measurementId === 'string' && /^G-[A-Z0-9]+$/i.test(measurementId);
}

function getGtag(windowObject) {
  windowObject.dataLayer = windowObject.dataLayer || [];
  windowObject.gtag = windowObject.gtag || function gtag() {
    windowObject.dataLayer.push(arguments);
  };

  return windowObject.gtag;
}

export function readAnalyticsConsent(storage) {
  const value = storage?.getItem(ANALYTICS_CONSENT_STORAGE_KEY);

  return Object.values(ANALYTICS_CONSENT).includes(value)
    ? value
    : null;
}

export function saveAnalyticsConsent(storage, consent) {
  storage.setItem(ANALYTICS_CONSENT_STORAGE_KEY, consent);
}

export function enableAnalytics(
  windowObject,
  measurementId = GA4_MEASUREMENT_ID
) {
  if (!isAnalyticsEnabled(measurementId)) return;

  const gtag = getGtag(windowObject);

  windowObject[`ga-disable-${measurementId}`] = false;
  gtag('consent', 'default', DENIED_CONSENT);
  gtag('consent', 'update', ACCEPTED_ANALYTICS_CONSENT);

  if (!windowObject.__revelationsGa4Configured) {
    gtag('js', new Date());
    gtag('config', measurementId);
    windowObject.__revelationsGa4Configured = true;
  }
}

export function disableAnalytics(
  windowObject,
  measurementId = GA4_MEASUREMENT_ID
) {
  if (!isAnalyticsEnabled(measurementId)) return;

  windowObject[`ga-disable-${measurementId}`] = true;

  if (windowObject.gtag) {
    windowObject.gtag('consent', 'update', DENIED_CONSENT);
  }
}

export function clearAnalyticsCookies(documentObject) {
  const names = documentObject.cookie
    .split(';')
    .map((cookie) => cookie.trim().split('=')[0])
    .filter((name) => name.startsWith('_ga'));

  for (const name of names) {
    documentObject.cookie = `${name}=; Max-Age=0; Path=/; SameSite=Lax`;
  }
}
