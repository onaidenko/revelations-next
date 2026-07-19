import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const source = await readFile(new URL('../app/api/revalidate/route.js', import.meta.url), 'utf8');
test('route has the signed POST-only revalidation contract', () => {
  for (const value of ['export async function POST', 'revalidateTag', 'revalidatePath', 'CMS_ARTICLES_CACHE_TAG', '{ expire: 0 }', 'REVELATIONS_REVALIDATION_SECRET', "'Cache-Control': 'no-store'"]) assert.match(source, new RegExp(value.replace(/[{}]/g, '\\$&')));
  for (const forbidden of ['export async function GET', 'updateTag', '"max"', "'max'", 'NEXT_PUBLIC_REVELATIONS_REVALIDATION_SECRET', 'console.log', 'console.error', 'production', 'staging']) assert.ok(!source.includes(forbidden));
  assert.doesNotMatch(source, /Response\.json\(payload|paths:/);
});
