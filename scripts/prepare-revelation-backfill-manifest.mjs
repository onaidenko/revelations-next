#!/usr/bin/env node

import { createHash } from 'node:crypto';
import { readFile, writeFile } from 'node:fs/promises';

const requiredEntryKeys = [
  'post_id',
  'slug',
  'title',
  'revelation',
  'word_count',
  'sha256',
];

function canonicalWordCount(value) {
  return (String(value).match(/[\p{L}\p{N}]+/gu) ?? []).length;
}

function sha256(value) {
  return createHash('sha256').update(value, 'utf8').digest('hex');
}

function validateManifest(manifest) {
  const failures = [];
  const entries = Array.isArray(manifest.entries) ? manifest.entries : [];

  if (manifest.schema_version !== 1) failures.push('schema_version');
  if (manifest.project !== 'REVELATIONS') failures.push('project');
  if (manifest.count !== 31 || entries.length !== 31) failures.push('count');

  const ids = new Set();
  const slugs = new Set();
  for (const [index, entry] of entries.entries()) {
    for (const key of requiredEntryKeys) {
      if (!(key in entry)) failures.push(`entry_${index}_missing_${key}`);
    }

    const revelation = String(entry.revelation ?? '');
    const wordCount = canonicalWordCount(revelation);
    if (!revelation || wordCount < 60 || wordCount > 150 || wordCount > 180) {
      failures.push(`entry_${index}_word_range`);
    }
    if (wordCount !== Number(entry.word_count)) {
      failures.push(`entry_${index}_word_count`);
    }
    if (sha256(revelation) !== entry.sha256) failures.push(`entry_${index}_sha256`);
    if (/[–—]/u.test(revelation)) failures.push(`entry_${index}_dash`);
    if (/\b(?:Revelations|REVELATIONs|revelations)\b/.test(revelation)) {
      failures.push(`entry_${index}_brand_case`);
    }
    ids.add(entry.post_id);
    slugs.add(entry.slug);
  }

  if (ids.size !== entries.length) failures.push('duplicate_post_id');
  if (slugs.size !== entries.length) failures.push('duplicate_slug');
  return failures;
}

const [input, output] = process.argv.slice(2);
if (!input || !output) {
  throw new Error('Usage: prepare-revelation-backfill-manifest.mjs INPUT OUTPUT');
}

const source = JSON.parse(await readFile(input, 'utf8'));
const originalTexts = source.entries.map(({ revelation, sha256: digest }) => ({ revelation, digest }));
const normalized = structuredClone(source);

for (const entry of normalized.entries) {
  entry.word_count = canonicalWordCount(entry.revelation);
}

for (const [index, entry] of normalized.entries.entries()) {
  const original = originalTexts[index];
  if (entry.revelation !== original.revelation || entry.sha256 !== original.digest) {
    throw new Error(`Entry ${index + 1} text or SHA changed unexpectedly.`);
  }
}

const failures = validateManifest(normalized);
if (failures.length > 0) {
  throw new Error(`Manifest validation failed: ${failures.join(', ')}`);
}

await writeFile(output, `${JSON.stringify(normalized, null, 2)}\n`, 'utf8');
console.log(JSON.stringify({
  entries: normalized.entries.length,
  output,
  sha256: sha256(await readFile(output, 'utf8')),
  validation: 'passed',
}, null, 2));
