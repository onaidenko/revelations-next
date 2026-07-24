import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');

test('legacy and additive CMS enrichment normalize predictably', () => {
  const cms = read('../lib/cms-articles.js');
  for (const fallback of ["revelation: null", "source_note: null", "editorial_note: null", "disclosure: null", "public_sources: []"]) assert.match(cms, new RegExp(fallback.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  assert.match(cms, /Array\.isArray\(article\.public_sources\)/);
  assert.match(cms, /source && source\.label && source\.url/);
});

test('public API exposes only explicit approved enrichment fields', () => {
  const api = read('../wordpress-cms/mu-plugins/revelations-public-api.php');
  for (const key of ['revelation', 'source_note', 'editorial_note', 'disclosure', 'public_sources']) assert.match(api, new RegExp(`'${key}'`));
  for (const privateKey of ['_rev_source_text', '_revelations_ai_fact_check_flags', '_revelations_ai_response_id']) assert.doesNotMatch(api, new RegExp(privateKey));
  assert.match(api, /revelations_enrichment_public_sources/);
});

test('article route keeps Stage 1 header and conditionally renders enrichment after hero', () => {
  const page = read('../app/[slug]/page.jsx');
  const hero = page.indexOf('article.cover_image &&');
  const revelation = page.indexOf('article.revelation &&');
  const body = page.indexOf('<ArticleBody');
  const taxonomy = page.indexOf('<ArticleTaxonomy');
  assert.ok(hero >= 0 && revelation > hero && body > revelation && taxonomy > body);
  assert.match(page, /THE REVELATION/);
  assert.match(page, /PUBLIC SOURCES/);
  assert.match(page, /rel="noreferrer"/);
  assert.match(page, /buildArticleHeaderLabels/);
  assert.match(page, /formatReadingTime\(article\.content\)/);
});

test('review, gate, and version paths protect every public enrichment field', () => {
  const review = read('../wordpress-cms/mu-plugins/revelations-editorial-review.php');
  const gate = read('../wordpress-cms/mu-plugins/revelations-editorial-publish-gate.php');
  const generate = read('../wordpress-cms/mu-plugins/revelations-editorial-ai-generate.php');
  const restore = read('../wordpress-cms/mu-plugins/revelations-editorial-version-restore.php');
  for (const key of ['revelation', 'source_note', 'editorial_note', 'disclosure', 'public_sources']) {
    assert.match(review, new RegExp(`'${key}'`));
    assert.match(gate, new RegExp(`'${key}'`));
  }
  for (const meta of ['revelations_revelation', 'revelations_source_note', 'revelations_editorial_note', 'revelations_disclosure', 'revelations_public_sources']) {
    assert.match(generate, new RegExp(meta));
    assert.match(restore, new RegExp(meta));
  }
  assert.match(restore, /restore_invalid_public_sources/);
  assert.match(restore, /restore_invalid_revelation/);
});
