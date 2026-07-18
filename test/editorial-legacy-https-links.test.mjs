import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const manifestUrl = new URL(
  '../wordpress-cms/bin/editorial-legacy-https-links.json',
  import.meta.url
);

const toolUrl = new URL(
  '../wordpress-cms/bin/migrate-editorial-legacy-https-links.php',
  import.meta.url
);

test('legacy link manifest contains only the five approved HTTPS replacements', async () => {
  const manifest = JSON.parse(
    await readFile(manifestUrl, 'utf8')
  );

  assert.equal(manifest.version, 1);
  assert.equal(manifest.replacements.length, 5);
  assert.deepEqual(
    [...new Set(manifest.replacements.map(({ post_id }) => post_id))],
    [73, 74]
  );

  for (const replacement of manifest.replacements) {
    assert.match(replacement.old_url, /^http:\/\//);
    assert.match(replacement.new_url, /^https:\/\//);
    assert.equal(
      replacement.new_url.slice('https'.length),
      replacement.old_url.slice('http'.length)
    );
  }
});

test('migration tool is content-only, backed up and idempotence-aware', async () => {
  const source = await readFile(toolUrl, 'utf8');

  assert.match(source, /'post_content'\s*=>/);
  assert.doesNotMatch(source, /wp_set_post_categories/);
  assert.doesNotMatch(source, /update_post_meta/);
  assert.match(source, /'already_applied'/);
  assert.match(source, /'backup_path'/);
  assert.match(source, /protected_before/);
  assert.match(source, /protected_after/);
});
