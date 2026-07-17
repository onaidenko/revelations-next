#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const scriptDirectory = path.dirname(
    fileURLToPath(import.meta.url)
);
const wordpressDirectory = path.dirname(scriptDirectory);
const pluginDirectory = path.join(
    wordpressDirectory,
    'mu-plugins'
);

const backendFile = path.join(
    pluginDirectory,
    'revelations-editorial-preview-candidate-save.php'
);
const techPreviewFile = path.join(
    pluginDirectory,
    'revelations-editorial-tech-preview.php'
);
const previewEngineFile = path.join(
    pluginDirectory,
    'revelations-editorial-preview-engine.php'
);
const storageFile = path.join(
    pluginDirectory,
    'revelations-editorial-storage.php'
);

const read = (file) => fs.readFileSync(file, 'utf8');
const backend = read(backendFile);
const techPreview = read(techPreviewFile);
const previewEngine = read(previewEngineFile);
const storage = read(storageFile);

const pluginFiles = fs.readdirSync(pluginDirectory)
    .filter((file) => file.endsWith('.php'))
    .map((file) => ({
        file,
        source: read(path.join(pluginDirectory, file))
    }));

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

const occurrences = (text, pattern) =>
    Array.from(text.matchAll(pattern)).length;

const helperPattern =
    /function\s+revelations_editorial_preview_candidate_exists\s*\(/g;
const hookPattern =
    /['"]admin_post_revelations_save_preview_candidate['"]/g;

check(
    1 === pluginFiles.reduce(
        (total, file) =>
            total + occurrences(file.source, helperPattern),
        0
    ),
    'duplicate helper has one definition across MU plugins'
);

check(
    1 === pluginFiles.reduce(
        (total, file) =>
            total + occurrences(file.source, hookPattern),
        0
    ),
    'admin_post candidate-save hook has one registration'
);

check(
    ! helperPattern.test(techPreview) &&
        ! hookPattern.test(techPreview),
    'Tech preview no longer owns shared backend definitions'
);

check(
    backend.includes(
        'Plugin Name: REVELATIONS Editorial Preview Candidate Save'
    ) &&
        backend.includes(
            'revelations_editorial_preview_candidate_exists'
        ) &&
        backend.includes(
            'admin_post_revelations_save_preview_candidate'
        ),
    'shared backend is owned by the dedicated MU plugin'
);

const sections = [
    'news',
    'people',
    'tech',
    'places'
];

for (const section of sections) {
    const file = path.join(
        pluginDirectory,
        `revelations-editorial-${section}-preview.php`
    );
    const source = read(file);
    const normalized = source.replace(/\s+/g, ' ');
    const actionPattern = new RegExp(
        'name="action"\\s+' +
        'value="\\s*revelations_save_preview_candidate\\s*"'
    );
    const sectionPattern = new RegExp(
        'name="preview_section"\\s+' +
        `value="${section}"`
    );
    const noncePattern = new RegExp(
        "'revelations_save_preview_candidate_'\\s*\\.\\s*" +
        `'${section}_'\\s*\\.\\s*\\$candidate_index`
    );

    check(
        actionPattern.test(normalized),
        `${section} preview retains candidate-save action`
    );

    check(
        sectionPattern.test(normalized),
        `${section} preview retains section field`
    );

    check(
        noncePattern.test(source),
        `${section} preview retains section-specific nonce`
    );
}

const securityTokens = [
    "current_user_can( 'manage_options' )",
    '$section = sanitize_key(',
    'revelations_editorial_preview_section_is_registered(',
    '$candidate_index = absint(',
    'check_admin_referer(',
    '$preview = get_transient(',
    "$preview['section'] ?? ''",
    '$candidate =',
    "'' === $title",
    "$candidate['duplicate_key'] ?? ''",
    'revelations_editorial_duplicate_key(',
    'revelations_editorial_preview_candidate_exists(',
    'if ( $existing_id > 0 )',
    '$candidate_id = wp_insert_post(',
    '$meta = array(',
    'update_post_meta(',
    "'candidate_saved' =>"
];

let securityCursor = -1;
const securityPositions = securityTokens.map((token) => {
    const position = backend.indexOf(
        token,
        securityCursor + 1
    );

    securityCursor = position;
    return position;
});

check(
    securityPositions.every((position) => position >= 0),
    'security, validation, duplicate and save checks retain order'
);

check(
    backend.includes(
        "'revelations_save_preview_candidate_' ."
    ) &&
        backend.includes('$section .') &&
        backend.includes("'_' .") &&
        backend.includes('$candidate_index'),
    'handler retains section-and-index nonce contract'
);

check(
    backend.includes(
        'revelations_editorial_preview_key('
    ) &&
        previewEngine.includes(
            "'rev_%s_preview_%d'"
        ) &&
        previewEngine.includes(
            'get_current_user_id()'
        ),
    'handler retains user-specific preview transient'
);

check(
    backend.includes(
        'revelations_editorial_preview_section_is_registered('
    ) &&
        sections.every(
            (section) =>
                previewEngine.includes(`'${section}' => array(`)
        ),
    'handler retains server-side four-section registry'
);

const hookPosition = backend.indexOf(
    "'admin_post_revelations_save_preview_candidate'"
);
const registryCallPosition = backend.indexOf(
    'revelations_editorial_preview_section_is_registered('
);
const previewKeyCallPosition = backend.indexOf(
    'revelations_editorial_preview_key('
);
const duplicateKeyCallPosition = backend.indexOf(
    'revelations_editorial_duplicate_key('
);

check(
    hookPosition >= 0 &&
        registryCallPosition > hookPosition &&
        previewKeyCallPosition > hookPosition &&
        duplicateKeyCallPosition > hookPosition,
    'cross-plugin dependencies are evaluated only in admin_post callback'
);

check(
    path.basename(backendFile) <
        path.basename(previewEngineFile) &&
        path.basename(backendFile) <
        path.basename(techPreviewFile),
    'alphabetical MU-plugin order does not require Tech preview first'
);

check(
    backend.includes(
        "'post_status' => 'publish'"
    ) &&
        backend.includes(
            "'post_type'   => 'rev_candidate'"
        ),
    'candidate post type and status remain unchanged'
);

const expectedMetaKeys = [
    '_rev_source_url',
    '_rev_source_name',
    '_rev_published_at',
    '_rev_fetched_at',
    '_rev_summary',
    '_rev_raw_excerpt',
    '_rev_section',
    '_rev_freshness_score',
    '_rev_relevance_score',
    '_rev_implementation_score',
    '_rev_hype_score',
    '_rev_fit_score',
    '_rev_total_score',
    '_rev_scoring_reason',
    '_rev_editorial_track',
    '_rev_duplicate_key',
    '_rev_run_id',
    '_rev_status',
    '_rev_error_message'
].sort();

const metaMatch = backend.match(
    /\$meta\s*=\s*array\(([\s\S]*?)\n\s*\);\n\n\s*foreach/
);
const actualMetaKeys = metaMatch
    ? Array.from(
        metaMatch[1].matchAll(
            /'(_rev_[a-z0-9_]+)'\s*=>/g
        ),
        (match) => match[1]
    ).sort()
    : [];

check(
    JSON.stringify(expectedMetaKeys) ===
        JSON.stringify(actualMetaKeys),
    'candidate-save meta mapping retains exact key set'
);

check(
    storage.includes("register_post_meta(") &&
        storage.includes("'rev_candidate'"),
    'candidate storage still registers rev_candidate metadata'
);

const storageMatch = storage.match(
    /\$candidate_meta\s*=\s*array\(([\s\S]*?)\n\s*\);\n\n\s*foreach/
);
const actualStorageKeys = storageMatch
    ? Array.from(
        storageMatch[1].matchAll(
            /'(_rev_[a-z0-9_]+)'\s*=>/g
        ),
        (match) => match[1]
    ).sort()
    : [];
const expectedStorageKeys = expectedMetaKeys.filter(
    (key) => '_rev_editorial_track' !== key
);

check(
    JSON.stringify(expectedStorageKeys) ===
        JSON.stringify(actualStorageKeys),
    'storage metadata contract retains exact registered key set'
);

check(
    backend.includes("'meta_key'       => '_rev_duplicate_key'") &&
        backend.includes("'publish',") &&
        backend.includes("'draft',") &&
        backend.includes("'pending',") &&
        backend.includes("'private',"),
    'duplicate query semantics remain unchanged'
);

const redirectTokens = [
    "'page' =>",
    "'revelations-editorial-desk'",
    "'view' =>",
    "'candidates'",
    "'candidate_section' =>",
    "'candidate_error' => 'expired'",
    "'candidate_error' => 'invalid'",
    "'candidate_duplicate' =>",
    "'candidate_error' => 'save'",
    "'candidate_saved' =>"
];

check(
    redirectTokens.every(
        (token) => backend.includes(token)
    ),
    'redirect parameters and result messages remain unchanged'
);

const total = passed + failed;

process.stdout.write(
    `\nPreview candidate-save diagnostics: ` +
    `${passed} passed, ${failed} failed, ${total} total.\n`
);

process.exit(failed === 0 ? 0 : 1);
