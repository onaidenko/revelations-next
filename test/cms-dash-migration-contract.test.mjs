import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../${path}`, import.meta.url), 'utf8');

test('raw dash plan is read-only, direct-storage sourced, and hash guarded', async () => {
  const source = await read('wordpress-cms/bin/build-dash-migration-plan.php');
  assert.match(source, /direct raw database rows/);
  assert.match(source, /current_raw_sha256/);
  assert.match(source, /untouched_bytes_sha256/);
  assert.match(source, /prepared-storage-level-update/);
  assert.doesNotMatch(source, /wp_update_post|update_post_meta|UPDATE\s/i);
  assert.doesNotMatch(source, /\/var\/www|\/private\/tmp|\/var\/backups/);
});

test('migration tooling has no incident-specific record recovery behavior', async () => {
  const sources = await Promise.all([
    read('wordpress-cms/bin/build-dash-migration-plan.php'),
    read('wordpress-cms/bin/revelations-dash-migration-lib.php'),
    read('wordpress-cms/bin/test-dash-migration-roundtrip.php'),
  ]);
  const combined = sources.join('\n');
  assert.doesNotMatch(combined, /record-(?:70|71|79|214|222|293)\b/);
  assert.doesNotMatch(combined, /\b(?:ID|post_id)\s*=\s*(?:70|71|79|214|222|293)\b/i);
});

test('migration design records atomic apply, rollback, and KSES boundaries', async () => {
  const design = await read('docs/cms-dash-migration-design.md');
  assert.match(design, /SELECT \.\.\. FOR UPDATE/);
  assert.match(design, /BINARY column/);
  assert.match(design, /Roll back\s+on any mismatch/);
  assert.match(design, /clean_post_cache/);
  assert.match(design, /signed frontend revalidation/);
  assert.match(design, /No capability manipulation or KSES/);
});
