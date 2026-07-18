import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
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

  assert.equal(
    decodeWordPressText('&quot;AI&quot;'),
    '"AI"'
  );
});

test('decodes exactly once', () => {
  assert.equal(
    decodeWordPressText('orchestration&amp;#8217;s &amp;amp; systems'),
    'orchestration&#8217;s &amp; systems'
  );
});

test('leaves non-string and already-decoded values unchanged', () => {
  assert.equal(decodeWordPressText('Morocco’s AI ecosystem'), 'Morocco’s AI ecosystem');
  assert.equal(decodeWordPressText(null), null);
});

test('WordPress HTML article content bypasses the plain-text decoder', async () => {
  const mapper = await readFile(
    new URL('../lib/cms-articles.js', import.meta.url),
    'utf8'
  );

  assert.match(
    mapper,
    /content:\s*article\.content\s*\|\|\s*''/
  );
  assert.doesNotMatch(
    mapper,
    /content:\s*decodeWordPressText/
  );
});
