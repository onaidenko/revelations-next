import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import test from 'node:test';

test(
  'taxonomy audit allows additive published articles without weakening approved-map checks',
  () => {
    const output = execFileSync(
      'python3',
      [
        'scripts/audit-editorial-taxonomy.py',
        '--self-test',
      ],
      {
        encoding: 'utf8',
      }
    );

    assert.match(
      output,
      /self_test=passed/
    );
  }
);
