import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { mkdtempSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';

test('backfill manifest normalizer changes only canonical word-count metadata', () => {
  const directory = mkdtempSync(join(tmpdir(), 'revelations-backfill-manifest-'));
  const input = join(directory, 'manifest-v2.json');
  const output = join(directory, 'manifest-v3.json');
  const revelation = Array.from({ length: 60 }, (_, index) => `word${index + 1}`).join(' ');
  const entries = Array.from({ length: 31 }, (_, index) => ({
    post_id: index + 1,
    slug: `article-${index + 1}`,
    title: `Article ${index + 1}`,
    revelation,
    word_count: 59,
    sha256: createHash('sha256').update(revelation, 'utf8').digest('hex'),
  }));
  writeFileSync(input, JSON.stringify({
    schema_version: 1,
    project: 'REVELATIONS',
    count: 31,
    entries,
  }));
  execFileSync('node', ['scripts/prepare-revelation-backfill-manifest.mjs', input, output], {
    cwd: process.cwd(),
    stdio: 'pipe',
  });

  const before = JSON.parse(readFileSync(input, 'utf8'));
  const after = JSON.parse(readFileSync(output, 'utf8'));
  assert.equal(after.count, 31);
  assert.equal(after.entries.length, 31);
  assert.equal(before.entries.length, after.entries.length);

  for (const [index, entry] of after.entries.entries()) {
    const original = before.entries[index];
    assert.equal(entry.revelation, original.revelation);
    assert.equal(entry.sha256, original.sha256);
    assert.equal(
      createHash('sha256').update(entry.revelation, 'utf8').digest('hex'),
      entry.sha256
    );
    assert.equal(
      (entry.revelation.match(/[\p{L}\p{N}]+/gu) ?? []).length,
      entry.word_count
    );
  }
});
