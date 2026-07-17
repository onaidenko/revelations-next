#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const binDirectory = path.dirname(fileURLToPath(import.meta.url));
const pluginDirectory = path.join(
    path.dirname(binDirectory),
    'mu-plugins'
);

const read = (name) =>
    fs.readFileSync(path.join(pluginDirectory, name), 'utf8');

const preview = read('revelations-editorial-unspoken-preview.php');
const engine = read('revelations-editorial-preview-engine.php');
const desk = read('revelations-editorial-desk.php');
const candidateBackend = read(
    'revelations-editorial-preview-candidate-save.php'
);
const scannerEngine = read(
    'revelations-editorial-scanner-engine.php'
);

let passed = 0;
let failed = 0;

const check = (condition, label) => {
    if (condition) {
        passed += 1;
        process.stdout.write(`PASS: ${label}\n`);
        return;
    }

    failed += 1;
    process.stdout.write(`FAIL: ${label}\n`);
};

const normalized = preview.replace(/\s+/g, ' ');

check(
    engine.includes("'unspoken' => array(") &&
        engine.includes(
            "'revelations_editorial_unspoken_scan_dry_run'"
        ) &&
        engine.includes("'default_enabled' =>") &&
        engine.includes('false,'),
    'Unspoken is registered in preview engine and disabled by default'
);

check(
    desk.includes(
        'revelations_editorial_render_unspoken_preview'
    ),
    'Editorial Desk renders the Unspoken preview'
);

check(
    preview.includes(
        "'admin_post_revelations_preview_unspoken_scan'"
    ) &&
        preview.includes(
            "check_admin_referer(\n" +
                "            'revelations_preview_unspoken_scan'"
        ) &&
        preview.includes(
            "revelations_editorial_generate_preview(\n" +
                "                'unspoken'"
        ),
    'preview action retains capability, nonce and shared runner contract'
);

check(
    /name="action"\s+value="\s*revelations_save_preview_candidate\s*"/.test(
        normalized
    ) &&
        /name="preview_section"\s+value="unspoken"/.test(
            normalized
        ) &&
        preview.includes(
            "'revelations_save_preview_candidate_' ."
        ) &&
        preview.includes("'unspoken_' .") &&
        preview.includes('$candidate_index'),
    'Unspoken uses the shared candidate-save action and section nonce'
);

check(
    candidateBackend.includes(
        'revelations_editorial_preview_section_is_registered('
    ) &&
        candidateBackend.includes(
            'revelations_editorial_preview_key('
        ),
    'shared backend validates Unspoken through the server registry'
);

check(
    normalized.includes('Single-source allegation') &&
        normalized.includes('Requires reputational review') &&
        normalized.includes('Evidence type:') &&
        normalized.includes(
            'does not establish the truth of the underlying claim'
        ),
    'preview renders the required allegation and evidence safeguards'
);

check(
    !/allegation (?:is )?(?:proven|verified|confirmed true)/i.test(
        preview
    ) &&
        !/fact checked/i.test(preview),
    'preview does not claim that an allegation is proven'
);

check(
    preview.includes('Secondary section advisory:') &&
        preview.includes(
            'The saved section remains Unspoken'
        ),
    'secondary section is advisory and does not change saved section'
);

check(
    preview.includes('Source diagnostics') &&
        preview.includes('skipped safely') &&
        scannerEngine.includes(
            "'source_results' =>\n            $source_results"
        ),
    'preview exposes safe per-source failure diagnostics'
);

check(
    scannerEngine.includes("'single_source_allegation' =>") &&
        scannerEngine.includes(
            "'requires_reputational_review' =>"
        ) &&
        scannerEngine.includes("'secondary_section' =>") &&
        scannerEngine.includes("'evidence_type' =>"),
    'shared preview format preserves transient safeguards'
);

check(
    !preview.includes('wp_insert_post(') &&
        !preview.includes('update_post_meta(') &&
        !preview.includes('wp_update_post('),
    'preview UI performs no candidate or draft writes itself'
);

const total = passed + failed;

process.stdout.write(
    `\nUnspoken preview diagnostics: ` +
        `${passed} passed, ${failed} failed, ${total} total.\n`
);

process.exit(failed === 0 ? 0 : 1);
