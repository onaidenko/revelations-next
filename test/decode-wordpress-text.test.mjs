import assert from 'node:assert/strict';
import test from 'node:test';

import { decodeWordPressText } from '../lib/decode-wordpress-text.js';

test('decodes WordPress named and numeric entities', () => {
  assert.equal(
    decodeWordPressText(
      'R&amp;D &mdash; founder&#39;s orchestration&#8217;s'
    ),
    "R&D — founder's orchestration’s"
  );

  assert.equal(
    decodeWordPressText('Hex apostrophe: &#x2019;'),
    'Hex apostrophe: ’'
  );
});

test('decodes legacy double-encoded entities with a bounded pass count', () => {
  assert.equal(
    decodeWordPressText('orchestration&amp;#8217;s &amp;amp; systems'),
    'orchestration’s & systems'
  );
});

test('leaves non-string and already-decoded values unchanged', () => {
  assert.equal(decodeWordPressText('Morocco’s AI ecosystem'), 'Morocco’s AI ecosystem');
  assert.equal(decodeWordPressText(null), null);
});
