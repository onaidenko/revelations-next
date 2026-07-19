import assert from 'node:assert/strict';
import test from 'node:test';

import { resolveShareUrl } from '../lib/share-url.js';

test('ShareButton URL uses the browser origin and pathname only', () => {
  assert.equal(
    resolveShareUrl({
      origin: 'https://revelations.me',
      pathname: '/ai-story',
      search: '?utm_source=share',
      hash: '#details',
    }, 'https://staging.revelations.me/ai-story'),
    'https://revelations.me/ai-story'
  );
});

test('ShareButton fallback strips query and fragment when location is unavailable', () => {
  assert.equal(
    resolveShareUrl(null, 'https://revelations.me/ai-story?utm=1#quote'),
    'https://revelations.me/ai-story'
  );
  assert.equal(resolveShareUrl(null, 'not a URL'), '');
});
