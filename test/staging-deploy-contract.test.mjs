import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (p) => readFile(new URL(p, import.meta.url), 'utf8');

test('staging artifact creation is exact-commit and isolated from tooling worktree', async () => {
  const source = await read('../scripts/prepare-staging-release.sh');
  for (const needed of [
    '--expected-commit', '--release-branch', 'release/ga4-consent-legal',
    'git worktree add --detach', 'source_branch', 'archive_sha256',
    '.next/standalone', '.next/static', '.next/BUILD_ID', 'public',
    "NEXT_PUBLIC_GA4_MEASUREMENT_ID=''",
    'Staging artifact contains the production GA4 Measurement ID',
  ]) {
    assert.ok(source.includes(needed), `missing prepared artifact safeguard ${needed}`);
  }
});

test('staging deploy validates manifest and immutable artifact before SSH', async () => {
  const source = await read('../scripts/deploy-staging.sh');
  for (const needed of [
    'archive_sha256', 'expected_commit', 'source_branch',
    '--expected-commit', '--release-branch', '--prepared-release',
    'Release branch is not allowlisted',
    'Release branch has advanced or SHA mismatch',
    'Staging artifact hash mismatch',
    'Artifact contains production GA4 ID',
    'STAGING_ARTIFACT_VALIDATED',
    'STAGING_UPLOAD_VERIFIED',
    'staging-remote-deploy.sh', 'staging-switch-transaction.sh',
  ]) {
    assert.ok(source.includes(needed), `missing local deploy guard: ${needed}`);
  }
  assert.ok(source.indexOf('STAGING_ARTIFACT_VALIDATED') < source.indexOf('ssh "${SSH_OPTIONS[@]}" "$REMOTE" "install'));
  assert.ok(source.indexOf('STAGING_UPLOAD_VERIFIED') < source.lastIndexOf('ssh "${SSH_OPTIONS[@]}"'));
  assert.doesNotMatch(source, /StrictHostKeyChecking=no/);
  assert.doesNotMatch(source, /\bmapfile\b/); // macOS Bash 3.2 compatibility
});

test('remote worker is staging-only with lock, backup, candidate and post-switch verification', async () => {
  const worker = await read('../scripts/staging-remote-deploy.sh');
  const txn = await read('../scripts/staging-switch-transaction.sh');
  for (const needed of [
    "SERVICE='revelations-staging.service'",
    "LIVE='/var/www/revelations-staging'",
    'flock -n 9',
    'cp -a "$LIVE" "$BACKUP/live-release"',
    'STAGING_BACKUP=',
    'staging_install_traps',
    'staging_switch_transaction',
    'candidate-assets.txt',
    'X-Robots-Tag',
  ]) {
    const matching = needed === 'X-Robots-Tag' ? 'x-robots-tag:' : needed;
    assert.ok(worker.toLowerCase().includes(matching.toLowerCase()), `missing worker guard ${needed}`);
  }
  for (const needed of [
    'SWITCH_REQUESTED=1', 'staging_on_exit',
    'ROLLBACK_SUCCESS', 'ROLLBACK_FAILED',
    'DEPLOY_CRITICAL=rollback_unverified',
    'staging_verify_old_runtime', 'staging_verify_new_runtime',
    'mv "$LIVE" "$PREVIOUS"', 'mv "$CANDIDATE" "$LIVE"',
  ]) {
    assert.ok(txn.includes(needed), `missing transaction guard ${needed}`);
  }
  assert.doesNotMatch(worker, /revelations-production.service/);
  assert.doesNotMatch(worker, /\/var\/www\/revelations-production/);
});
