import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const source = await readFile(
  new URL('../app/robots.js', import.meta.url),
  'utf8'
);

const executable = source
  .replace(
    "import { SITE_URL } from '@/lib/site';",
    "const SITE_URL = 'https://revelations.me';"
  )
  .replace('export default function robots()', 'function robots()')
  .concat('\nexport { robots };\n');

const { robots } = await import(
  `data:text/javascript;base64,${Buffer.from(executable).toString('base64')}`
);

test('robots declares both canonical production sitemaps exactly once', () => {
  const result = robots();

  assert.deepEqual(result.sitemap, [
    'https://revelations.me/sitemap.xml',
    'https://revelations.me/news-sitemap.xml',
  ]);
  assert.equal(new Set(result.sitemap).size, 2);
  assert.equal(result.sitemap.filter((url) => url.includes('staging')).length, 0);
  assert.equal(result.sitemap.filter((url) => url.includes('cms.')).length, 0);
});

test('robots preserves the existing crawler rules', () => {
  assert.deepEqual(robots().rules, {
    userAgent: '*',
    allow: '/',
    disallow: [
      '/admin/',
      '/editorial-desk/',
      '/private/',
    ],
  });
});
