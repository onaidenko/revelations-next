#!/usr/bin/env bash

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_BIN="${PHP_BIN:-php}"
NODE_BIN="${NODE_BIN:-node}"
GENERATION_FAILED=0
OUTSIDE_GENERATION_FAILED=0

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
    GENERATION_FAILED=$((GENERATION_FAILED + 1))
}

run_outside_generation() {
    local label="$1"
    shift

    printf '\n=== %s ===\n' "$label"
    "$@"
    local exit_code=$?
    if [ "$exit_code" -eq 0 ]; then
        printf '%s: PASS (exit %d)\n' "$label" "$exit_code"
        return
    fi
    printf '%s: FAIL outside the generation suite (exit %d)\n' "$label" "$exit_code"
    OUTSIDE_GENERATION_FAILED=$((OUTSIDE_GENERATION_FAILED + 1))
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
    'Stage 6b component: research, source authority and evidence selection' \
    "$PHP_BIN" \
    "$SCRIPT_DIR/test-editorial-ai-research.php"

run_suite \
    'Stage 7 component: editorial review metadata' \
    "$PHP_BIN" \
    "$SCRIPT_DIR/test-editorial-ai-review-metadata.php"

run_suite \
    'Stage 8 integration: complete AI generation pipeline' \
    "$PHP_BIN" \
    "$SCRIPT_DIR/test-editorial-ai-generation-integration.php"

run_outside_generation \
    'Unspoken component: strict scanner policy' \
    "$PHP_BIN" \
    "$SCRIPT_DIR/test-editorial-unspoken-scanner.php"

run_outside_generation \
    'Unspoken component: preview and candidate-save integration' \
    "$NODE_BIN" \
    "$SCRIPT_DIR/test-editorial-unspoken-preview.mjs"

run_outside_generation \
    'Scanner runtime: settings and structured rejection diagnostics' \
    "$PHP_BIN" \
    "$SCRIPT_DIR/test-editorial-scanner-runtime.php"

printf '\n=== Separate upstream regression ===\n'
printf 'The global AI gate is upstream and is not part of the generation integration count.\n'

run_outside_generation \
    'Upstream component: global scanner AI gate' \
    "$PHP_BIN" \
    "$SCRIPT_DIR/test-editorial-ai-gate.php"

printf '\n=== Regression suite result ===\n'

if [ "$GENERATION_FAILED" -eq 0 ]; then
    printf 'Generation regression suite passed.\n'
    if [ "$OUTSIDE_GENERATION_FAILED" -gt 0 ]; then
        printf '%d scanner diagnostic suite(s) failed outside the generation suite.\n' "$OUTSIDE_GENERATION_FAILED"
    fi
    exit 0
fi

printf '%d generation diagnostic suite(s) failed.\n' "$GENERATION_FAILED"
exit 1
