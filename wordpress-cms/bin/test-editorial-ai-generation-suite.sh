#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_BIN="${PHP_BIN:-php}"
NODE_BIN="${NODE_BIN:-node}"
FAILED_SUITES=0

run_suite() {
    local label="$1"
    shift

    printf '\n=== %s ===\n' "$label"

    "$@"
    local exit_code=$?

    if [ "$exit_code" -eq 0 ]; then
        printf '%s: PASS (exit %d)\n' "$label" "$exit_code"
        return
    fi

    printf '%s: FAIL (exit %d)\n' "$label" "$exit_code"
    FAILED_SUITES=$((FAILED_SUITES + 1))
}

printf 'REVELATIONS AI generation regression suite\n'
printf 'Component diagnostics retain their own case counts.\n'

run_suite \
    'Stage 1 component: AI configuration controls' \
    "$PHP_BIN" \
    "$SCRIPT_DIR/test-editorial-ai-config.php"

run_suite \
    'Stage 2 component: editorial action interfaces' \
    "$NODE_BIN" \
    "$SCRIPT_DIR/test-editorial-action-ui.mjs"

run_suite \
    'Stage 3 component: shared candidate-save backend' \
    "$NODE_BIN" \
    "$SCRIPT_DIR/test-editorial-preview-candidate-save.mjs"

run_suite \
    'Stage 4 component: generation profiles' \
    "$PHP_BIN" \
    "$SCRIPT_DIR/test-editorial-ai-generation-profiles.php"

run_suite \
    'Stage 5 component: generation schema and storage' \
    "$PHP_BIN" \
    "$SCRIPT_DIR/test-editorial-ai-generation-schema.php"

run_suite \
    'Stage 6 component: generated claim validation' \
    "$PHP_BIN" \
    "$SCRIPT_DIR/test-editorial-ai-generation-validation.php"

run_suite \
    'Stage 7 component: editorial review metadata' \
    "$PHP_BIN" \
    "$SCRIPT_DIR/test-editorial-ai-review-metadata.php"

run_suite \
    'Stage 8 integration: complete AI generation pipeline' \
    "$PHP_BIN" \
    "$SCRIPT_DIR/test-editorial-ai-generation-integration.php"

printf '\n=== Separate upstream regression ===\n'
printf 'The global AI gate is upstream and is not part of the generation integration count.\n'

run_suite \
    'Upstream component: global scanner AI gate' \
    "$PHP_BIN" \
    "$SCRIPT_DIR/test-editorial-ai-gate.php"

printf '\n=== Regression suite result ===\n'

if [ "$FAILED_SUITES" -eq 0 ]; then
    printf 'All diagnostic suites passed.\n'
    exit 0
fi

printf '%d diagnostic suite(s) failed.\n' "$FAILED_SUITES"
exit 1
