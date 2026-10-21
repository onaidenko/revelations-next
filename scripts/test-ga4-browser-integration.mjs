#!/usr/bin/env node

import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import { existsSync } from 'node:fs';
import { mkdtemp, rm } from 'node:fs/promises';
import net from 'node:net';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { spawn } from 'node:child_process';

const baseUrl = process.env.GA4_BROWSER_TEST_BASE_URL || 'http://127.0.0.1:3100';
const measurementId = process.env.GA4_BROWSER_TEST_MEASUREMENT_ID || 'G-GZSZRHS50G';
const chromeBin = process.env.CHROME_BIN || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const debugPort = Number(process.env.GA4_BROWSER_TEST_DEBUG_PORT || '9231');
const reuseDebugBrowser = process.env.GA4_BROWSER_TEST_REUSE_DEBUG_PORT === '1';
const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

if (!reuseDebugBrowser && !existsSync(chromeBin)) {
  throw new Error(`Set CHROME_BIN to a Chrome-compatible browser: ${chromeBin}`);
}

function openWebSocket(url) {
  const parsed = new URL(url);
  const key = crypto.randomBytes(16).toString('base64');
  const raw = net.createConnection({ host: parsed.hostname, port: Number(parsed.port) });
  let buffered = Buffer.alloc(0);
  let upgraded = false;
  let onMessage = () => {};

  const consume = () => {
    while (buffered.length >= 2) {
      const first = buffered[0];
      let length = buffered[1] & 0x7f;
      let offset = 2;
      if (length === 126) {
        if (buffered.length < 4) return;
        length = buffered.readUInt16BE(2);
        offset = 4;
      } else if (length === 127) {
        if (buffered.length < 10) return;
        length = Number(buffered.readBigUInt64BE(2));
        offset = 10;
      }
      if (buffered.length < offset + length) return;
      const payload = buffered.subarray(offset, offset + length);
      buffered = buffered.subarray(offset + length);
      if ((first & 0x0f) === 1) onMessage(payload.toString('utf8'));
    }
  };

  return new Promise((resolve, reject) => {
    raw.once('error', reject);
    raw.on('data', (chunk) => {
      buffered = Buffer.concat([buffered, chunk]);
      if (!upgraded) {
        const end = buffered.indexOf('\r\n\r\n');
        if (end === -1) return;
        const header = buffered.subarray(0, end).toString('utf8');
        if (!header.startsWith('HTTP/1.1 101')) {
          reject(new Error(`WebSocket handshake failed: ${header.split('\r\n')[0]}`));
          return;
        }
        upgraded = true;
        buffered = buffered.subarray(end + 4);
        resolve({
          close: () => raw.end(Buffer.from([0x88, 0x80, 0, 0, 0, 0])),
          onMessage: (callback) => { onMessage = callback; },
          send: (value) => {
            const payload = Buffer.from(value);
            const mask = crypto.randomBytes(4);
            let header;
            if (payload.length < 126) header = Buffer.from([0x81, 0x80 | payload.length]);
            else if (payload.length <= 0xffff) {
              header = Buffer.alloc(4);
              header[0] = 0x81;
              header[1] = 0xfe;
              header.writeUInt16BE(payload.length, 2);
            } else {
              header = Buffer.alloc(10);
              header[0] = 0x81;
              header[1] = 0xff;
              header.writeBigUInt64BE(BigInt(payload.length), 2);
            }
            const masked = Buffer.alloc(payload.length);
            for (let index = 0; index < payload.length; index += 1) {
              masked[index] = payload[index] ^ mask[index % 4];
            }
            raw.write(Buffer.concat([header, mask, masked]));
          },
        });
        consume();
        return;
      }
      consume();
    });
    raw.once('connect', () => raw.write(
      `GET ${parsed.pathname}${parsed.search} HTTP/1.1\r\nHost: ${parsed.host}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: ${key}\r\nSec-WebSocket-Version: 13\r\n\r\n`
    ));
  });
}

async function waitForChrome() {
  for (let attempt = 0; attempt < 40; attempt += 1) {
    try {
      return await fetch(`http://127.0.0.1:${debugPort}/json/version`).then((response) => response.json());
    } catch {
      await sleep(100);
    }
  }
  throw new Error('Chrome DevTools endpoint did not start');
}

const profile = reuseDebugBrowser ? null : await mkdtemp(join(tmpdir(), 'revelations-ga4-browser-'));
const chromeArgs = [
  '--headless=new', '--no-sandbox', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
  '--remote-debugging-address=127.0.0.1',
  `--remote-debugging-port=${debugPort}`, `--user-data-dir=${profile}`, 'about:blank',
];
if (new URL(baseUrl).hostname === 'revelations.me' && new URL(baseUrl).port) {
  chromeArgs.unshift('--host-resolver-rules=MAP revelations.me 127.0.0.1');
}
const chrome = reuseDebugBrowser ? null : spawn(chromeBin, chromeArgs, { stdio: 'ignore' });

try {
  await waitForChrome();
  const target = await fetch(`http://127.0.0.1:${debugPort}/json/new?about:blank`, {
    method: 'PUT',
  }).then((response) => response.json());
  const socket = await openWebSocket(target.webSocketDebuggerUrl);
  const pending = new Map();
  const requests = [];
  let commandId = 0;

  socket.onMessage((data) => {
    const message = JSON.parse(data);
    if (message.id) {
      const entry = pending.get(message.id);
      if (entry) {
        pending.delete(message.id);
        message.error ? entry.reject(new Error(JSON.stringify(message.error))) : entry.resolve(message.result);
      }
      return;
    }
    if (message.method === 'Network.requestWillBeSent') requests.push(message.params.request.url);
    if (message.method === 'Fetch.requestPaused') {
      void send('Fetch.fulfillRequest', {
        requestId: message.params.requestId,
        responseCode: 204,
      });
    }
  });

  const send = (method, params = {}) => {
    const id = ++commandId;
    socket.send(JSON.stringify({ id, method, params }));
    return new Promise((resolve, reject) => pending.set(id, { resolve, reject }));
  };
  const evaluate = async (expression) => {
    const result = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.text);
    return result.result.value;
  };
  const navigate = async (url) => {
    await send('Page.navigate', { url });
    await sleep(1800);
  };
  const gaRequests = (start) => requests.slice(start).filter((url) =>
    /googletagmanager\.com\/gtag\/js|google-analytics\.com\/g\/collect/.test(url)
  );
  const pageViews = (start) => gaRequests(start).filter((url) => url.includes('en=page_view'));
  const waitForPageViews = async (start, expected, description) => {
    for (let attempt = 0; attempt < 40; attempt += 1) {
      const observed = pageViews(start);
      if (observed.length >= expected) {
        await sleep(1000);
        assert.equal(
          pageViews(start).length,
          expected,
          `${description}; saw ${JSON.stringify(gaRequests(start))}`
        );
        return;
      }
      await sleep(250);
    }
    assert.equal(
      pageViews(start).length,
      expected,
      `${description}; saw ${JSON.stringify(gaRequests(start))}`
    );
  };
  const click = (label) => evaluate(`(() => { const button = [...document.querySelectorAll('button')].find((item) => item.textContent.trim() === ${JSON.stringify(label)}); if (!button) throw new Error(${JSON.stringify(`${label} button missing`)}); button.click(); })()`);
  const clickLink = async (selector) => {
    const rect = await evaluate(`(() => { const element = [...document.querySelectorAll(${JSON.stringify(selector)})].find((item) => { const box = item.getBoundingClientRect(); return box.width > 0 && box.height > 0; }); if (!element) throw new Error('Visible link missing: ' + ${JSON.stringify(selector)}); element.scrollIntoView({ block: 'center' }); const box = element.getBoundingClientRect(); return { x: box.left + box.width / 2, y: box.top + box.height / 2 }; })()`);
    await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: rect.x, y: rect.y, button: 'none' });
    await send('Input.dispatchMouseEvent', { type: 'mousePressed', x: rect.x, y: rect.y, button: 'left', buttons: 1, clickCount: 1 });
    await send('Input.dispatchMouseEvent', { type: 'mouseReleased', x: rect.x, y: rect.y, button: 'left', buttons: 0, clickCount: 1 });
  };
  const waitForPath = async (path) => {
    for (let attempt = 0; attempt < 120; attempt += 1) {
      if (await evaluate('location.pathname') === path) return;
      await sleep(100);
    }
    throw new Error(`Timed out waiting for ${path}`);
  };

  await send('Page.enable');
  await send('Runtime.enable');
  await send('Network.enable');
  await send('Emulation.setDeviceMetricsOverride', {
    width: 1280,
    height: 900,
    deviceScaleFactor: 1,
    mobile: false,
  });
  await send('Fetch.enable', {
    patterns: [{
      urlPattern: '*://*.google-analytics.com/g/collect*',
      requestStage: 'Request',
    }],
  });
  await send('Storage.clearDataForOrigin', {
    origin: new URL(baseUrl).origin,
    storageTypes: 'all',
  });
  await send('Network.clearBrowserCookies');

  let marker = requests.length;
  await navigate(`${baseUrl}/?ga4-browser-test=before-accept`);
  assert.deepEqual(gaRequests(marker), [], 'GA must not load before Accept');
  assert.equal(await evaluate('document.querySelectorAll("script#ga4-tag").length'), 0);
  assert.equal(await evaluate('Object.hasOwn(window, "dataLayer")'), false);

  marker = requests.length;
  await click('Accept analytics');
  await sleep(2500);
  assert.equal(gaRequests(marker).filter((url) => url.includes('gtag/js')).length, 1);
  assert.equal(pageViews(marker).length, 1, 'Accept must queue exactly one initial page_view');
  assert.equal(await evaluate('document.querySelectorAll("script#ga4-tag").length'), 1);
  assert.equal(await evaluate(`(window.dataLayer || []).filter((entry) => entry[0] === 'config' && entry[1] === '${measurementId}').length`), 1);
  assert.equal(await evaluate('Object.prototype.toString.call(window.dataLayer[0])'), '[object Arguments]');

  await sleep(8000);

  marker = requests.length;
  await clickLink('a[href="/news"]');
  await waitForPath('/news');
  await waitForPageViews(marker, 1, 'Next.js section navigation must create one page_view');

  marker = requests.length;
  const articlePath = await evaluate(`(() => { const item = [...document.querySelectorAll('a[href^="/"]')].find((link) => /^\\/[a-z0-9][a-z0-9-]+$/.test(link.getAttribute('href')) && !['/news', '/people', '/tech', '/places', '/art', '/unspoken', '/podcast', '/access', '/about', '/archive', '/contact', '/advertise'].includes(link.getAttribute('href'))); if (!item) throw new Error('Article link missing'); return item.getAttribute('href'); })()`);
  await clickLink(`a[href="${articlePath}"]`);
  await waitForPath(articlePath);
  await waitForPageViews(marker, 1, 'Next.js article navigation must create one page_view');

  marker = requests.length;
  await evaluate("history.pushState({}, '', '/ga4-browser-history-check')");
  await waitForPageViews(marker, 1, 'Browser History API changes must create one page_view');

  await click('Cookie settings');
  await click('Reject');
  marker = requests.length;
  await clickLink('a[href="/"]');
  await waitForPath('/');
  await sleep(1500);
  assert.deepEqual(gaRequests(marker), [], 'Withdrawal must stop future analytics requests');
  assert.equal(await evaluate(`window['ga-disable-${measurementId}'] === true`), true);

  await click('Cookie settings');
  marker = requests.length;
  await click('Accept analytics');
  await sleep(1000);
  assert.equal(gaRequests(marker).filter((url) => url.includes('gtag/js')).length, 0);
  assert.equal(await evaluate('document.querySelectorAll("script#ga4-tag").length'), 1);
  assert.equal(await evaluate(`(window.dataLayer || []).filter((entry) => entry[0] === 'config' && entry[1] === '${measurementId}').length`), 1);

  marker = requests.length;
  await evaluate("history.pushState({}, '', '/ga4-browser-reaccept-check')");
  await waitForPageViews(marker, 1, 'Accept after withdrawal must resume one page_view per History API change');

  await send('Storage.clearDataForOrigin', { origin: new URL(baseUrl).origin, storageTypes: 'all' });
  await send('Network.clearBrowserCookies');
  marker = requests.length;
  await navigate(`${baseUrl}/?ga4-browser-test=fresh-reject`);
  await click('Reject');
  await sleep(600);
  assert.deepEqual(gaRequests(marker), [], 'Fresh Reject must not load analytics');

  console.log('GA4_BROWSER_INTEGRATION_SUCCESS');
  socket.close();
} finally {
  chrome?.kill('SIGTERM');
  if (profile) await rm(profile, { recursive: true, force: true });
}
