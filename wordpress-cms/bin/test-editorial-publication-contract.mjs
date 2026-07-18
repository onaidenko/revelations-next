import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const root = new URL('./', import.meta.url);
const read = (name) => fs.readFileSync(new URL(name, root), 'utf8');
const review = read('../mu-plugins/revelations-editorial-review.php');
const gate = read('../mu-plugins/revelations-editorial-publish-gate.php');
const contract = read('../mu-plugins/revelations-editorial-category-contract.php');
const core = read('../mu-plugins/revelations-cms-core.php');
const editor = read('../mu-plugins/revelations-cms-editor.js');

const checks = [
  ['field fingerprints cover all controlled fields', ['title', 'content', 'excerpt', 'category', 'tags', 'seo_title', 'seo_description', 'displayed_author', 'ai_review_metadata'].every((key) => review.includes(`'${key}'`))],
  ['review hashes omit featured image', !review.includes("'featured_image'") && !review.includes("'thumbnail'")],
  ['new review stores field hashes and clears changed fields', review.includes('_revelations_editorial_review_field_hashes') && review.includes('_revelations_editorial_review_changed_fields')],
  ['review rejects invalid category contract', review.includes('revelations_editorial_category_contract_validate')],
  ['single category contract uses source section registry', contract.includes('revelations_editorial_sections')],
  ['contract rejects zero, multiple, uncategorized and non-editorial categories', ['category_missing', 'category_multiple', 'category_uncategorized', 'category_not_allowed'].every((key) => contract.includes(key))],
  ['publish gate reads incoming REST categories and tags', gate.includes("$request->has_param( 'categories' )") && gate.includes("$request->has_param( 'tags' )")],
  ['publish gate validates category before publish', gate.includes('revelations_editorial_category_contract_validate')],
  ['editor UI removes standard category panel', editor.includes("removeEditorPanel('taxonomy-panel-category')")],
  ['editor UI uses a single SelectControl and replaces categories', editor.includes("label: 'Editorial category'") && editor.includes('categories: value ? [Number(value)] : []')],
  ['editor UI exposes saved-version readiness wording', editor.includes('Saved version is ready to publish')],
  ['editor payload provides allowed categories', core.includes('editorialCategories')],
];
for (const [label, ok] of checks) assert.ok(ok, label);
new vm.Script(editor, { filename: 'revelations-cms-editor.js' });
console.log(`Publication contract diagnostics: ${checks.length} passed, 0 failed, ${checks.length} total.`);
