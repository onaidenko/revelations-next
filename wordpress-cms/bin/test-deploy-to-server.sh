#!/usr/bin/env bash

# Network-free argument and safety-contract fixture for the canonical CMS
# deploy script. It deliberately exercises only failures before SSH.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEPLOY="$SCRIPT_DIR/deploy-to-server.sh"

expect_reject() {
    local label="$1"
    shift
    if "$DEPLOY" "$@" >/dev/null 2>&1; then
        echo "FAIL: $label" >&2
        exit 1
    fi
    echo "PASS: $label"
}

bash -n "$DEPLOY"
expect_reject 'targeted mode requires files' --dry-run --files
expect_reject 'dry run requires an explicit scope' --dry-run
expect_reject 'deploy requires an explicit scope' --deploy
expect_reject 'full and files are mutually exclusive' --deploy --full --files revelations-cms-core.php
expect_reject 'path traversal is rejected' --dry-run --files ../revelations-cms-core.php
expect_reject 'nested path is rejected' --dry-run --files bin/test-editorial-ai-research.php
expect_reject 'outside-file extension is rejected' --dry-run --files README.md
expect_reject 'unknown option is rejected' --dry-run --unexpected

PLUGIN_DIR="$SCRIPT_DIR/../mu-plugins"
SYMLINK_FIXTURE="$PLUGIN_DIR/revelations-deploy-test-link.php"
DIRECTORY_FIXTURE="$PLUGIN_DIR/revelations-deploy-test-directory.php"
cleanup_fixtures() { rm -f -- "$SYMLINK_FIXTURE"; rmdir "$DIRECTORY_FIXTURE" 2>/dev/null || true; }
trap cleanup_fixtures EXIT
ln -s revelations-cms-core.php "$SYMLINK_FIXTURE"
mkdir "$DIRECTORY_FIXTURE"
expect_reject 'symlink is rejected' --dry-run --files revelations-deploy-test-link.php
expect_reject 'directory is rejected' --dry-run --files revelations-deploy-test-directory.php
expect_reject 'duplicate targeted file is rejected' --dry-run --files revelations-cms-core.php revelations-cms-core.php

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
