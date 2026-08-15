import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import {
  legacyArticleRedirectSlug,
  selectLiveArticleCatalog,
} from '../lib/article-catalog-policy.js';

const legacy = {
  legacy_id: 'legacy-001',
  slug: 'legacy-story',
  title: 'Legacy story',
  source: 'legacy',
};

const publishedCms = {
  id: 101,
  legacy_id: 'legacy-001',
  slug: 'cms-story',
  title: 'CMS version',
  source: 'wordpress',
};

test('CMS-configured catalog uses only published CMS articles', () => {
  const catalog = selectLiveArticleCatalog({
    cmsConfigured: true,
    cmsArticles: [publishedCms],
    legacyArticles: [legacy],
  });

  assert.deepEqual(catalog, [publishedCms]);
  assert.equal(catalog[0].title, 'CMS version');
});

test('trashed, permanently deleted, and unpublished CMS articles cannot resurrect from JSON', () => {
  for (const status of ['trash', 'deleted', 'draft']) {
    const catalog = selectLiveArticleCatalog({
      cmsConfigured: true,
      cmsArticles: [],
      legacyArticles: [{ ...legacy, status }],
    });

    assert.deepEqual(catalog, [], status);
  }
});

test('published CMS migration keeps its legacy-ID redirect, while absent records fail closed', () => {
  assert.equal(
    legacyArticleRedirectSlug('legacy-001', [publishedCms]),
    'cms-story'
  );
  assert.equal(
    legacyArticleRedirectSlug('cms-story', [publishedCms]),
    'cms-story'
  );
  assert.equal(legacyArticleRedirectSlug('legacy-001', []), null);
  assert.equal(legacyArticleRedirectSlug('legacy-story', []), null);
});

test('local legacy mode remains explicit, while configured CMS fetch failures cannot select JSON', async () => {
  assert.deepEqual(
    selectLiveArticleCatalog({
      cmsConfigured: false,
      cmsArticles: [],
      legacyArticles: [legacy],
    }),
    [legacy]
  );

  assert.deepEqual(
    selectLiveArticleCatalog({
      cmsConfigured: true,
      cmsArticles: [],
      legacyArticles: [legacy],
    }),
    []
  );

  const source = await readFile(
    new URL('../lib/cms-articles.js', import.meta.url),
    'utf8'
  );

  assert.match(source, /if \(!CMS_API_URL\)/);
  assert.match(source, /cmsArticles: await fetchCmsArticles\(\)/);
  assert.doesNotMatch(source, /mergeArticles/);
  assert.doesNotMatch(source, /Falling back to local articles/);
});
