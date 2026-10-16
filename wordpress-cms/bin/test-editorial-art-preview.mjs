#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const binDirectory = path.dirname(fileURLToPath(import.meta.url));
const pluginDirectory = path.join(path.dirname(binDirectory), 'mu-plugins');
const read = (name) => fs.readFileSync(path.join(pluginDirectory, name), 'utf8');
const preview = read('revelations-editorial-art-preview.php');
const engine = read('revelations-editorial-preview-engine.php');
const desk = read('revelations-editorial-desk.php');
const scanner = read('revelations-editorial-art-scanner.php');
const normalized = preview.replace(/\s+/g, ' ');
let passed = 0;
let failed = 0;
const check = (condition, label) => {
  if (condition) {
    passed += 1;
    process.stdout.write(`PASS: ${label}\n`);
  } else {
    failed += 1;
    process.stdout.write(`FAIL: ${label}\n`);
  }
};

check(
  engine.includes("'art' => array(") &&
    engine.includes("'revelations_editorial_art_scan_dry_run'") &&
    engine.includes("'default_enabled' =>\n                false,"),
  'Art is registered in the preview engine and disabled by default'
);
check(
  desk.includes('revelations_editorial_render_art_preview') &&
    preview.includes("'admin_post_revelations_preview_art_scan'") &&
    preview.includes("check_admin_referer( 'revelations_preview_art_scan' )") &&
    preview.includes("revelations_editorial_generate_preview( 'art' )"),
  'Editorial Desk exposes the Art preview through the shared runner'
);
check(
  /name="action"\s+value="revelations_save_preview_candidate"/.test(normalized) &&
    /name="preview_section"\s+value="art"/.test(normalized) &&
    preview.includes("'revelations_save_preview_candidate_' . 'art_' . $candidate_index"),
  'Art uses the shared candidate-save action with the Art nonce'
);
check(
  preview.includes('Art relevance gate filtered:') &&
    preview.includes('This section does not use the global AI gate.') &&
    preview.includes('Art gate diagnostics') &&
    scanner.includes("'art_relevance_gate'"),
  'Art preview reports its dedicated relevance gate without AI wording'
);
check(
  !preview.includes('wp_insert_post(') &&
    !preview.includes('update_post_meta(') &&
    !preview.includes('wp_update_post('),
  'Art preview does not create candidates or drafts automatically'
);

process.stdout.write(`\nArt preview diagnostics: ${passed} passed, ${failed} failed.\n`);
process.exit(failed === 0 ? 0 : 1);
