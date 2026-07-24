import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');
test('author entity, exact migration, and public privacy contracts exist', () => {
  const authors = read('../wordpress-cms/mu-plugins/revelations-editorial-authors.php');
  const api = read('../wordpress-cms/mu-plugins/revelations-public-api.php');
  for (const value of ['rev_author', '_revelations_author_profile_ids', "'person'", "'organization'", 'Julia U.', 'Julia Yupiterskaya', 'Julia Upiterskaya', 'julia-upiterskaya', 'editorial-team']) assert.match(authors, new RegExp(value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  assert.match(authors, /revelations_author_migration_report/);
  assert.match(authors, /revelations_author_is_public_ready/);
  assert.match(api, /'author_profiles'/);
  assert.match(api, /'meta_value'=>\(string\) \$author_id, 'compare'=>'LIKE'/);
  assert.doesNotMatch(api, /'meta_value'=>'"' \. \$author_id/);
  for (const privateValue of ['email', 'capabilities', 'edit_url']) assert.doesNotMatch(api, new RegExp(`'${privateValue}'`));
});
test('frontend preserves legacy bylines and supports canonical ordered profiles', () => {
  const cms = read('../lib/cms-articles.js'); const page = read('../app/[slug]/page.jsx'); const seo = read('../lib/seo.js');
  assert.match(cms, /formatArticleAuthors/); assert.match(cms, /author_profiles: \[\]/); assert.match(page, /ABOUT THE AUTHOR/); assert.match(page, /\/authors\/\$\{author\.slug\}/); assert.match(seo, /article\.author_profiles\?\.length/);
});
test('author profile routes and sitemap are public-ready only', () => {
  const route = read('../app/authors/[slug]/page.jsx'); const sitemap = read('../app/sitemap.js');
  assert.match(route, /getPublicAuthorBySlug/); assert.match(route, /notFound\(\)/); assert.match(route, /#\$\{type\.toLowerCase\(\)\}/); assert.match(sitemap, /getPublicAuthors/);
});
test('canonical article identity is independent from public profile readiness', () => {
  const authors = read('../wordpress-cms/mu-plugins/revelations-editorial-authors.php');
  const api = read('../wordpress-cms/mu-plugins/revelations-public-api.php');
  const page = read('../app/[slug]/page.jsx');
  const seo = read('../lib/seo.js');
  assert.match(authors, /revelations_author_article_data/);
  assert.match(authors, /'is_public_profile'=>\$public/);
  assert.match(api, /revelations_author_article_data/);
  assert.match(page, /author\.is_public_profile \? <Link/);
  assert.match(page, /filter\(\(author\) => author\.is_public_profile\)/);
  assert.match(seo, /profile\.is_public_profile && profile\.url/);
  assert.match(seo, /'Organization' : 'Person'/);
});
