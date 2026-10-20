import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import {
  ANALYTICS_CONSENT,
  ANALYTICS_CONSENT_STORAGE_KEY,
  clearAnalyticsCookies,
  disableAnalytics,
  enableAnalytics,
  isAnalyticsEnabled,
  readAnalyticsConsent,
  saveAnalyticsConsent,
} from '../lib/analytics-consent.js';

const TEST_GA4_MEASUREMENT_ID = 'G-TEST1234';

function createStorage(initial = {}) {
  const values = new Map(Object.entries(initial));

  return {
    getItem(key) {
      return values.get(key) ?? null;
    },
    setItem(key, value) {
      values.set(key, value);
    },
  };
}

test('GA4 is not rendered before an explicit accepted consent choice', async () => {
  const [layout, boundary] = await Promise.all([
    readFile(new URL('../app/layout.jsx', import.meta.url), 'utf8'),
    readFile(
      new URL('../components/analytics-consent.jsx', import.meta.url),
      'utf8'
    ),
  ]);

  assert.doesNotMatch(layout, /googletagmanager/);
  assert.match(
    boundary,
    /consent === ANALYTICS_CONSENT\.ACCEPTED && \(/
  );
  assert.match(boundary, /<Script[\s\S]*?googletagmanager/);
});

test('an unset Measurement ID leaves GA4 fully disabled', () => {
  const storage = createStorage();
  const windowObject = {};

  enableAnalytics(windowObject, null);
  disableAnalytics(windowObject, null);

  assert.equal(isAnalyticsEnabled(null), false);
  assert.equal(windowObject.dataLayer, undefined);
  assert.equal(windowObject.gtag, undefined);
  assert.equal(Object.keys(windowObject).length, 0);
  assert.equal(readAnalyticsConsent(storage), null);
});

test('reject stores the choice and blocks configured analytics without initializing gtag', () => {
  const storage = createStorage();
  const windowObject = {};

  saveAnalyticsConsent(storage, ANALYTICS_CONSENT.REJECTED);
  disableAnalytics(windowObject, TEST_GA4_MEASUREMENT_ID);

  assert.equal(readAnalyticsConsent(storage), ANALYTICS_CONSENT.REJECTED);
  assert.equal(windowObject.dataLayer, undefined);
  assert.equal(windowObject.gtag, undefined);
  assert.equal(windowObject[`ga-disable-${TEST_GA4_MEASUREMENT_ID}`], true);
});

test('accept persists analytics consent and configures GA4 once with advertising denied', () => {
  const storage = createStorage();
  const windowObject = {};

  saveAnalyticsConsent(storage, ANALYTICS_CONSENT.ACCEPTED);
  enableAnalytics(windowObject, TEST_GA4_MEASUREMENT_ID);
  enableAnalytics(windowObject, TEST_GA4_MEASUREMENT_ID);

  assert.equal(
    readAnalyticsConsent(storage),
    ANALYTICS_CONSENT.ACCEPTED
  );
  assert.equal(windowObject[`ga-disable-${TEST_GA4_MEASUREMENT_ID}`], false);
  assert.equal(
    windowObject.dataLayer.filter(
      ([command, id]) =>
        command === 'config' && id === TEST_GA4_MEASUREMENT_ID
    ).length,
    1
  );
  assert.deepEqual(windowObject.dataLayer[0], [
    'consent',
    'default',
    {
      ad_storage: 'denied',
      ad_user_data: 'denied',
      ad_personalization: 'denied',
      analytics_storage: 'denied',
    },
  ]);
  assert.deepEqual(windowObject.dataLayer[1], [
    'consent',
    'update',
    {
      ad_storage: 'denied',
      ad_user_data: 'denied',
      ad_personalization: 'denied',
      analytics_storage: 'granted',
    },
  ]);
});

test('revoking analytics consent disables future collection and clears GA cookies', () => {
  const windowObject = {};
  const deletedCookies = [];
  const documentObject = {
    cookie: '_ga=identifier; _ga_ABC=stream; other=value',
  };

  Object.defineProperty(documentObject, 'cookie', {
    get() {
      return '_ga=identifier; _ga_ABC=stream; other=value';
    },
    set(value) {
      deletedCookies.push(value);
    },
  });

  enableAnalytics(windowObject, TEST_GA4_MEASUREMENT_ID);
  disableAnalytics(windowObject, TEST_GA4_MEASUREMENT_ID);
  clearAnalyticsCookies(documentObject);

  assert.equal(windowObject[`ga-disable-${TEST_GA4_MEASUREMENT_ID}`], true);
  assert.deepEqual(windowObject.dataLayer.at(-1), [
    'consent',
    'update',
    {
      ad_storage: 'denied',
      ad_user_data: 'denied',
      ad_personalization: 'denied',
      analytics_storage: 'denied',
    },
  ]);
  assert.deepEqual(deletedCookies, [
    '_ga=; Max-Age=0; Path=/; SameSite=Lax',
    '_ga_ABC=; Max-Age=0; Path=/; SameSite=Lax',
  ]);
});

test('invalid persisted values require a fresh choice', () => {
  const storage = createStorage({
    [ANALYTICS_CONSENT_STORAGE_KEY]: 'granted',
  });

  assert.equal(readAnalyticsConsent(storage), null);
});

test('the consent boundary relies on enhanced measurement without manual page views', async () => {
  const [boundary, consentLibrary] = await Promise.all([
    readFile(
      new URL('../components/analytics-consent.jsx', import.meta.url),
      'utf8'
    ),
    readFile(
      new URL('../lib/analytics-consent.js', import.meta.url),
      'utf8'
    ),
  ]);

  assert.doesNotMatch(boundary, /gtag\('event', 'page_view'/);
  assert.doesNotMatch(boundary, /send_page_view/);
  assert.match(consentLibrary, /NEXT_PUBLIC_GA4_MEASUREMENT_ID/);
  assert.match(consentLibrary, /__revelationsGa4Configured/);
});
