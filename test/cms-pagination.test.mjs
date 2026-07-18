import assert from 'node:assert/strict';
import test from 'node:test';

import { fetchAllCmsArticlePages } from '../lib/cms-pagination.js';

function items(from, count) {
  return Array.from(
    { length: count },
    (_, index) => ({
      id: from + index,
      slug: `article-${from + index}`,
    })
  );
}

test('fetches every CMS page beyond the former 100-item limit', async () => {
  const pages = [items(1, 100), items(101, 50)];
  const result = await fetchAllCmsArticlePages(
    async (page) => ({
      items: pages[page - 1] || [],
      pagination: { total_pages: 2 },
    })
  );

  assert.equal(result.length, 150);
  assert.equal(result.at(-1).id, 150);
});

test('fetches a 1000-item CMS collection through ten pages', async () => {
  const result = await fetchAllCmsArticlePages(
    async (page) => ({
      items: items((page - 1) * 100 + 1, 100),
      pagination: { total_pages: 10 },
    })
  );

  assert.equal(result.length, 1000);
  assert.equal(result.at(-1).id, 1000);
});

test('stops on an incomplete page when pagination metadata is absent', async () => {
  const calls = [];
  const result = await fetchAllCmsArticlePages(
    async (page) => {
      calls.push(page);
      return { items: page === 1 ? items(1, 3) : items(4, 1) };
    },
    { pageSize: 3 }
  );

  assert.equal(result.length, 4);
  assert.deepEqual(calls, [1, 2]);
});

test('stops a repeated CMS page and deduplicates stable IDs', async () => {
  const repeated = items(1, 100);
  const result = await fetchAllCmsArticlePages(
    async () => ({ items: repeated }),
    { maxPages: 20 }
  );

  assert.equal(result.length, 100);
});
