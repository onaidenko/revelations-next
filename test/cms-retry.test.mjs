import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import { retryCmsOperation } from '../lib/cms-retry.js';

test('retries transient CMS failures and returns the successful result', async () => {
  const attempts = [];
  const delays = [];

  const result = await retryCmsOperation(
    async (attempt) => {
      attempts.push(attempt);

      if (attempt < 3) {
        throw new Error(`transient-${attempt}`);
      }

      return { items: [1, 2, 3] };
    },
    {
      attempts: 3,
      delayMs: 100,
      sleep: async (milliseconds) => {
        delays.push(milliseconds);
      },
    }
  );

  assert.deepEqual(result, { items: [1, 2, 3] });
  assert.deepEqual(attempts, [1, 2, 3]);
  assert.deepEqual(delays, [100, 200]);
});

test('throws the final CMS error instead of converting it to empty data', async () => {
  const failures = [
    new Error('first'),
    new Error('second'),
    new Error('final'),
  ];
  let index = 0;

  await assert.rejects(
    retryCmsOperation(
      async () => {
        const error = failures[index];
        index += 1;
        throw error;
      },
      {
        attempts: 3,
        sleep: async () => {},
      }
    ),
    (error) => error === failures.at(-1)
  );

  assert.equal(index, 3);
});

test('CMS article policy preserves cached renders on fetch failures', async () => {
  const source = await readFile(
    new URL('../lib/cms-articles.js', import.meta.url),
    'utf8'
  );

  assert.match(source, /retryCmsOperation/);
  assert.match(source, /CMS_REQUEST_TIMEOUT_MS = 15000/);
  assert.match(source, /Article fetch failed; preserving cached render/);
  assert.match(source, /throw error;/);
  assert.doesNotMatch(source, /Falling back to local articles/);
});
