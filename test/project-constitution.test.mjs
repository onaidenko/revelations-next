import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');

const constitutionMarkers = [
  'REVELATIONS_BRAND_NAME_REQUIRED',
  'ASCII_HYPHEN_ONLY',
  'CANONICAL_BRAND_TAGLINE_REQUIRED',
  'CANONICAL_JULIA_UPITERSKAYA',
  'STAGING_VISUAL_APPROVAL_REQUIRED',
  'PUBLIC_PRIVATE_EVIDENCE_BOUNDARY',
];

test('Project Constitution retains every required permanent marker', () => {
  const constitution = read('../docs/PROJECT_CONSTITUTION.md');

  for (const marker of constitutionMarkers) {
    assert.match(constitution, new RegExp(marker));
  }

  assert.match(constitution, /Born as a podcast\. Built as a media platform\./);
  assert.match(constitution, /Julia Upiterskaya/);
  assert.match(constitution, /product-owner approval/);
  assert.match(constitution, /historical imported public editorial content/);
  assert.match(constitution, /technical values\s+where that could change/);
  assert.match(constitution, /ASCII compound-word hyphens/);
  assert.match(constitution, /may\s+be applied to legacy public CMS editorial text/);
});

test('critical governance surfaces reference the Constitution', () => {
  for (const path of [
    '../AGENTS.md',
    '../docs/context/workflow.md',
    '../docs/context/decisions.md',
  ]) {
    assert.match(read(path), /PROJECT_CONSTITUTION\.md/);
  }
});
