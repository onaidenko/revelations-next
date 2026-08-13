#!/usr/bin/env bash

# Network-free argument and safety-contract fixture for the canonical CMS
# deploy script. It deliberately exercises only failures before SSH.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEPLOY="$SCRIPT_DIR/deploy-to-server.sh"

expect_reject_text() {
    local label="$1"
    local expected="$2"
    shift
    shift
    local output
    if output="$("$DEPLOY" "$@" 2>&1)"; then
        echo "FAIL: $label" >&2
        exit 1
    fi
    if [[ "$output" != *"$expected"* ]]; then
        echo "FAIL: $label did not report: $expected" >&2
        echo "$output" >&2
        exit 1
    fi
    echo "PASS: $label"
}

bash -n "$DEPLOY"
expect_reject_text 'targeted mode requires files' 'Targeted mode requires at least one MU-plugin file.' --dry-run --files
expect_reject_text 'dry run requires an explicit scope' 'Exactly one deployment scope is required' --dry-run
expect_reject_text 'deploy requires an explicit scope' 'Exactly one deployment scope is required' --deploy
expect_reject_text 'full and files are mutually exclusive' '--full cannot be combined' --deploy --full --files revelations-cms-core.php
expect_reject_text 'path traversal is rejected' 'Invalid targeted MU-plugin filename' --dry-run --files ../revelations-cms-core.php
expect_reject_text 'nested path is rejected' 'Invalid targeted MU-plugin filename' --dry-run --files bin/test-editorial-ai-research.php
expect_reject_text 'outside-file extension is rejected' 'Invalid targeted MU-plugin filename' --dry-run --files README.md
expect_reject_text 'unknown option is rejected' 'Expected exactly one deployment scope' --dry-run --unexpected

PLUGIN_DIR="$SCRIPT_DIR/../mu-plugins"
SYMLINK_FIXTURE="$PLUGIN_DIR/revelations-deploy-test-link.php"
DIRECTORY_FIXTURE="$PLUGIN_DIR/revelations-deploy-test-directory.php"
cleanup_fixtures() { rm -f -- "$SYMLINK_FIXTURE"; rmdir "$DIRECTORY_FIXTURE" 2>/dev/null || true; }
trap cleanup_fixtures EXIT
ln -s revelations-cms-core.php "$SYMLINK_FIXTURE"
mkdir "$DIRECTORY_FIXTURE"
expect_reject_text 'symlink is rejected' 'Targeted file does not exist as a regular local MU-plugin' --dry-run --files revelations-deploy-test-link.php
expect_reject_text 'directory is rejected' 'Targeted file does not exist as a regular local MU-plugin' --dry-run --files revelations-deploy-test-directory.php
expect_reject_text 'duplicate targeted file is rejected' 'Duplicate targeted file: revelations-cms-core.php' --dry-run --files revelations-cms-core.php revelations-cms-core.php

ssh() { printf 'NETWORK_FREE_SSH_STUB\n'; }
export -f ssh
positive_dry_run_output="$("$DEPLOY" --dry-run --files revelations-cms-core.php)"
if [[ "$positive_dry_run_output" != *'TARGETED CMS DRY RUN'* || "$positive_dry_run_output" != *'NETWORK_FREE_SSH_STUB'* || "$positive_dry_run_output" != *'Dry run complete. No remote files were staged or changed.'* ]]; then
    echo 'FAIL: valid targeted dry run did not complete through the network-free SSH fixture' >&2
    exit 1
fi
echo 'PASS: valid targeted dry run completes through the network-free SSH fixture'

targeted_function="$(sed -n '/^targeted_deploy()/,/^full_deploy()/p' "$DEPLOY")"
if printf '%s\n' "$targeted_function" | grep -Eq '^[[:space:]]*(rsync|RSYNC_ARGS)[[:space:]]|--delete-delay'; then
    echo 'FAIL: targeted deploy contains an rsync/delete operation' >&2
    exit 1
fi
targeted_dry_run_function="$(sed -n '/^targeted_dry_run()/,/^targeted_deploy()/p' "$DEPLOY")"
if printf '%s\n' "$targeted_dry_run_function" | grep -Eq '^[[:space:]]*(scp|mktemp|rsync|cp|install|chown|chmod)[[:space:]]|^[^#]*--delete-delay'; then
    echo 'FAIL: targeted dry run contains a remote mutation operation' >&2
    exit 1
fi
full_function="$(sed -n '/^full_deploy()/,/^if \[ "\$TARGETED"/p' "$DEPLOY")"
if ! printf '%s\n' "$full_function" | grep -q -- '--delete-delay'; then
    echo 'FAIL: explicit full deploy no longer contains its delete-delay sync' >&2
    exit 1
fi
if [ "$(grep -c -- '--delete-delay' "$DEPLOY")" -ne 1 ]; then
    echo 'FAIL: delete-delay is not exclusive to explicit full deploy' >&2
    exit 1
fi
if grep -q '192\.248\.179\.164' "$DEPLOY"; then
    echo 'FAIL: deploy script contains a production raw IP' >&2
    exit 1
fi
if ! grep -q '^SERVER="revelations-prod"' "$DEPLOY"; then
    echo 'FAIL: deploy script does not use the production SSH alias' >&2
    exit 1
fi

echo 'PASS: full and targeted mode remain separate'
echo 'CMS deployment argument diagnostics passed.'
