#!/usr/bin/env node

import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const source = readFileSync(
  new URL('./provision-editorial-art-category.php', import.meta.url),
  'utf8'
);

test('Art category provisioner is explicit, scoped and idempotent', () => {
  assert.match(source, /PHP_SAPI !== 'cli'/);
  assert.match(source, /get_term_by\( 'slug', 'art', 'category' \)/);
  assert.match(source, /wp_insert_term\( 'Art', 'category', array\( 'slug' => 'art' \) \)/);
  assert.match(source, /'art' !== \$existing->slug/);
  assert.match(source, /'Art' !== \$existing->name/);
});
