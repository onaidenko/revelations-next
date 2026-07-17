#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const scriptDirectory = path.dirname(
    fileURLToPath(import.meta.url)
);
const wordpressDirectory = path.dirname(scriptDirectory);
const pluginDirectory = path.join(
    wordpressDirectory,
    'mu-plugins'
);

const files = {
    sourceDraft: path.join(
        pluginDirectory,
        'revelations-editorial-source-draft-ui.php'
    ),
    longActions: path.join(
        pluginDirectory,
        'revelations-editorial-long-actions-ui.php'
    ),
    aiReadiness: path.join(
        pluginDirectory,
        'revelations-editorial-ai-readiness.php'
    ),
    scanUi: path.join(
        pluginDirectory,
        'revelations-editorial-scan-ui.php'
    ),
    candidateSaveUi: path.join(
        pluginDirectory,
        'revelations-editorial-candidate-save-ui.php'
    )
};

const source = Object.fromEntries(
    Object.entries(files).map(([name, file]) => [
        name,
        fs.readFileSync(file, 'utf8')
    ])
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

const occurrences = (text, needle) =>
    text.split(needle).length - 1;

const tagCount = (text, pattern) =>
    (text.match(pattern) || []).length;

const extractBlocks = (text, tag) => {
    const pattern = new RegExp(
        `<${tag}(?:\\s[^>]*)?>([\\s\\S]*?)<\\/${tag}>`,
        'gi'
    );

    return Array.from(
        text.matchAll(pattern),
        (match) => match[1]
    );
};

const scripts = {
    sourceDraft: extractBlocks(source.sourceDraft, 'script'),
    longActions: extractBlocks(source.longActions, 'script'),
    aiReadiness: extractBlocks(source.aiReadiness, 'script'),
    scanUi: extractBlocks(source.scanUi, 'script'),
    candidateSaveUi: extractBlocks(
        source.candidateSaveUi,
        'script'
    )
};

const styles = {
    sourceDraft: extractBlocks(source.sourceDraft, 'style'),
    longActions: extractBlocks(source.longActions, 'style'),
    scanUi: extractBlocks(source.scanUi, 'style'),
    candidateSaveUi: extractBlocks(
        source.candidateSaveUi,
        'style'
    )
};

check(
    1 === scripts.sourceDraft.length &&
        1 === styles.sourceDraft.length &&
        1 === tagCount(source.sourceDraft, /<script(?:\s[^>]*)?>/gi) &&
        1 === tagCount(source.sourceDraft, /<\/script>/gi) &&
        1 === tagCount(source.sourceDraft, /<style(?:\s[^>]*)?>/gi) &&
        1 === tagCount(source.sourceDraft, /<\/style>/gi),
    'source draft UI has one complete script and style block'
);

check(
    1 === scripts.longActions.length &&
        1 === styles.longActions.length &&
        1 === tagCount(source.longActions, /<script(?:\s[^>]*)?>/gi) &&
        1 === tagCount(source.longActions, /<\/script>/gi) &&
        1 === tagCount(source.longActions, /<style(?:\s[^>]*)?>/gi) &&
        1 === tagCount(source.longActions, /<\/style>/gi),
    'long actions UI has one complete script and style block'
);

check(
    1 === scripts.scanUi.length &&
        1 === styles.scanUi.length,
    'scanner UI has one complete script and style block'
);

check(
    1 === scripts.candidateSaveUi.length &&
        1 === styles.candidateSaveUi.length,
    'candidate-save UI has one complete script and style block'
);

check(
    ! scripts.sourceDraft[0]?.includes('</style>') &&
        ! scripts.sourceDraft[0]?.includes('<script'),
    'source draft script contains no broken style/script fragment'
);

for (const [name, blocks] of Object.entries(scripts)) {
    for (const [index, script] of blocks.entries()) {
        let syntaxIsValid = true;

        try {
            new vm.Script(script, {
                filename: `${name}-inline-${index + 1}.js`
            });
        } catch (error) {
            syntaxIsValid = false;
            process.stdout.write(
                `JS syntax error: ${error.message}\n`
            );
        }

        check(
            syntaxIsValid,
            `${name} inline script ${index + 1} has valid syntax`
        );
    }
}

const sourceDraftScript = scripts.sourceDraft.join('\n');
const longActionsScript = scripts.longActions.join('\n');
const aiReadinessScript = scripts.aiReadiness.join('\n');
const actionScripts = Object.values(scripts);

check(
    1 === occurrences(
        sourceDraftScript,
        "form.addEventListener('submit'"
    ),
    'source draft forms have one submit listener definition'
);

check(
    1 === occurrences(
        longActionsScript,
        "form.addEventListener('submit'"
    ),
    'long action forms have one submit listener definition'
);

check(
    0 === occurrences(
        aiReadinessScript,
        "addEventListener('submit'"
    ) &&
        0 === scripts.aiReadiness.length &&
        ! source.aiReadiness.includes(
            'revelations-ai-submit-feedback'
        ),
    'AI readiness adds no duplicate generation listener'
);

check(
    sourceDraftScript.includes(
        'revelationsSourceDraftUiReady'
    ) &&
        longActionsScript.includes(
            'revelationsLongActionUiReady'
        ),
    'both action UIs guard against repeated listener setup'
);

check(
    1 === occurrences(
        sourceDraftScript,
        'HTMLFormElement.prototype.submit.call'
    ) &&
        1 === occurrences(
            longActionsScript,
            'HTMLFormElement.prototype.submit.call'
        ),
    'each handler has one native form submission path'
);

check(
    sourceDraftScript.includes(
        "getSubmitControls(form).forEach"
    ) &&
        ! sourceDraftScript.includes('.flatMap('),
    'source draft UI scopes disabled controls to its form'
);

check(
    longActionsScript.includes(
        "getSubmitControls(form).forEach"
    ) &&
        ! longActionsScript.includes(
            'allSubmitControls'
        ) &&
        ! longActionsScript.includes('.flatMap('),
    'long actions UI scopes disabled controls to its form'
);

check(
    sourceDraftScript.includes('catch (error)') &&
        sourceDraftScript.includes(
            'revelations-source-draft-error'
        ) &&
        sourceDraftScript.includes('resetForm(form)'),
    'source draft UI restores controls after client failure'
);

check(
    longActionsScript.includes('catch (error)') &&
        longActionsScript.includes(
            'revelations-long-action-error'
        ) &&
        longActionsScript.includes('resetForm(form)'),
    'long actions UI restores controls after client failure'
);

const expectedActions = [
    'revelations_create_source_draft',
    'revelations_fetch_source_snapshot',
    'revelations_generate_editorial_draft',
    'revelations_test_openai_connection',
    'revelations_restore_ai_version'
];

for (const action of expectedActions) {
    check(
        1 === actionScripts.filter(
            (script) => script.join('\n').includes(action)
        ).length,
        `${action} has one action UI owner`
    );
}

check(
    ! longActionsScript.includes(
        'revelations_create_source_draft'
    ),
    'source draft and shared long-action selectors do not overlap'
);

check(
    longActionsScript.includes(
        'revelations_restore_ai_version'
    ),
    'Restore AI version receives long-action feedback'
);

check(
    /form\.dataset\s*\.revelationsSubmitting\s*===\s*'1'/.test(
        sourceDraftScript
    ) &&
        /form\.dataset\s*\.revelationsSubmitting\s*===\s*'1'/.test(
            longActionsScript
        ),
    'both handlers reject duplicate submits'
);

const total = passed + failed;

process.stdout.write(
    `\nEditorial action UI diagnostics: ` +
    `${passed} passed, ${failed} failed, ${total} total.\n`
);

process.exit(failed === 0 ? 0 : 1);
