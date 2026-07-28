import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(path, import.meta.url), 'utf8');

test('staging release packages and validates standalone static assets', async () => {
  const [prepare, deploy] = await Promise.all([
    read('../scripts/prepare-staging-release.sh'),
    read('../scripts/deploy-staging.sh'),
  ]);
  assert.match(prepare, /\.next\/standalone/);
  for (const source of [prepare, deploy]) {
    assert.match(source, /\.next\/static/);
    assert.match(source, /\.next\/BUILD_ID/);
    assert.match(source, /public/);
  }
  assert.match(deploy, /sha256sum/);
  assert.match(deploy, /RELEASE_COMMIT/);
  assert.match(deploy, /candidate/);
  assert.match(deploy, /_next\/static/);
  assert.match(deploy, /curl -sS -o \/dev\/null -w/);
});
