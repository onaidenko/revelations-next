import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { spawnSync } from 'node:child_process';
import test from 'node:test';

const deployPath = new URL(
  '../scripts/deploy-production.sh',
  import.meta.url
);
const verifierPath = new URL(
  '../scripts/verify-production-release.py',
  import.meta.url
);

test('production deploy is fail-closed and transfers runtime environment only after candidate verification', async () => {
  const source = await readFile(deployPath, 'utf8');

  for (const required of [
    'set -euo pipefail',
    'REMOTE="revelations-prod"',
    'SSH_OPTIONS=(',
    '-o BatchMode=yes',
    '-o ConnectTimeout=15',
    '-o ServerAliveInterval=15',
    '-o ServerAliveCountMax=4',
    '-o LogLevel=ERROR',
    '--expected-commit',
    '--confirm',
    'deploy-production-${EXPECTED_COMMIT:0:12}',
    'test -z "$(git status --porcelain=v1 --untracked-files=all)"',
    'artifact_runtime_env=absent',
    'cat > "$CANDIDATE/.env.production"',
    'NEXT_PUBLIC_SITE_URL=$SITE_URL',
    'REVELATIONS_CMS_API_URL=$CMS_API_URL',
    'stop_candidate',
    'cp -a "$RUNTIME_ENV_SOURCE" "$CANDIDATE/.env.production"',
    'cmp -s',
    'runtime_environment_transfer_failed',
    'trap on_exit EXIT',
    'public_candidate_match_failed',
    'RUNTIME_ENV_TRANSFERRED=yes',
    'PUBLIC_MANIFEST_MATCH=yes',
    'systemctl show -p ControlGroup --value "$SERVICE"',
    '/sys/fs/cgroup',
    'cgroup.procs',
    'find_service_ports()',
    'service_loopback_not_ready_after_switch',
    'UPLOAD_START',
    'UPLOAD_COMPLETE',
    'REMOTE_DEPLOY_START',
    'REMOTE_PREFLIGHT',
    'ARCHIVE_VERIFIED',
    'CANDIDATE_CREATED',
    'CANDIDATE_STARTED',
    'CANDIDATE_VERIFIED',
    'BACKUP_CREATED',
    'ATOMIC_SWITCH_START',
    'SERVICE_STARTED',
    'PUBLIC_VERIFICATION_START',
    'PUBLIC_VERIFICATION_COMPLETE',
    'DEPLOY_SUCCESS',
    'VERIFY_ATTEMPT=$index/$attempts base=$base',
    'VERIFY_SUCCESS=$index/$attempts base=$base',
    '| tee "$REMOTE_LOG"',
    'PIPELINE_STATUS=("${PIPESTATUS[@]}")',
    'REMOTE_STATUS="${PIPELINE_STATUS[0]}"',
    'TEE_STATUS="${PIPELINE_STATUS[1]}"',
  ]) {
    assert.ok(
      source.includes(required),
      `missing deployment safeguard: ${required}`
    );
  }

  const candidateSnapshot = source.indexOf(
    'candidate_snapshot_failed'
  );
  const candidateStop = source.indexOf(
    '\nstop_candidate\n',
    candidateSnapshot
  );
  const runtimeCopy = source.indexOf(
    'cp -a "$RUNTIME_ENV_SOURCE" "$CANDIDATE/.env.production"'
  );
  const switchRelease = source.indexOf(
    'mv "$CANDIDATE" "$LIVE_ROOT"'
  );
  const publicFailure = source.indexOf(
    'fail "public_candidate_match_failed"'
  );
  const successMarker = source.indexOf(
    'echo "DEPLOY_RESULT=success"'
  );

  assert.ok(candidateSnapshot >= 0);
  assert.ok(candidateStop > candidateSnapshot);
  assert.ok(runtimeCopy > candidateStop);
  assert.ok(switchRelease > runtimeCopy);
  assert.ok(publicFailure > switchRelease);
  assert.ok(successMarker > publicFailure);

  assert.match(
    source,
    /ssh[\s\S]*?2>&1\s+<<'REMOTE_SCRIPT'\s*\|\s*tee\s+"\$REMOTE_LOG"/
  );
  assert.ok(
    source.indexOf('if [[ "$REMOTE_STATUS" -ne 0 ]]') <
      source.indexOf('if [[ "$TEE_STATUS" -ne 0 ]]'),
    'remote SSH failure must take precedence over a tee failure'
  );
  assert.match(
    source,
    /ssh\s+"\$\{SSH_OPTIONS\[@\]\}"\s+\\\n\s*"\$REMOTE"\s+\\\n\s*"rm -rf '\$REMOTE_TMP'"/
  );
  assert.match(
    source,
    /ssh\s+"\$\{SSH_OPTIONS\[@\]\}"\s+\\\n\s*"\$REMOTE"\s+\\\n\s*"mkdir -m 700 -p '\$REMOTE_TMP'"/
  );
  assert.match(source, /scp\s+\\\n\s*"\$\{SSH_OPTIONS\[@\]\}"/);
  assert.match(
    source,
    /ssh\s+"\$\{SSH_OPTIONS\[@\]}"\s+"\$REMOTE"\s+"bash -s --/
  );
  assert.doesNotMatch(source, /StrictHostKeyChecking=no/);
  const rawIps =
    source.match(/\b(?:\d{1,3}\.){3}\d{1,3}\b/g) || [];

  assert.deepEqual(
    [...new Set(rawIps)],
    ['127.0.0.1']
  );
  assert.doesNotMatch(
    source,
    /PUBLIC_OK=.*test "\$PUBLIC_OK"/
  );
  assert.doesNotMatch(
    source,
    /\/proc\/\$\{?pid\}?\/cwd/
  );
  assert.doesNotMatch(
    source,
    /root in cmdline/
  );

  const serviceStart = source.indexOf(
    'systemctl start "$SERVICE"'
  );
  const readinessLoop = source.indexOf(
    'for _ in $(seq 1 40); do',
    serviceStart
  );
  const cgroupLookup = source.indexOf(
    'systemctl show -p ControlGroup --value "$SERVICE"',
    serviceStart
  );
  const loopbackFailure = source.indexOf(
    'fail "service_loopback_not_ready_after_switch"'
  );
  const publicManifestFailure = source.indexOf(
    'fail "public_candidate_match_failed"'
  );

  assert.ok(serviceStart >= 0);
  assert.ok(readinessLoop > serviceStart);
  assert.ok(cgroupLookup > readinessLoop);
  assert.ok(loopbackFailure > cgroupLookup);
  assert.ok(publicManifestFailure > loopbackFailure);
  assert.doesNotMatch(source, /service_ports_not_found/);
  assert.doesNotMatch(source, /service_loopback_health_failed/);
});

test('production release verifier is sitemap-driven and passes its self-test', async () => {
  const source = await readFile(verifierPath, 'utf8');

  for (const required of [
    'TAXONOMY_PREFIXES',
    'main sitemap contains no taxonomy hubs',
    'taxonomy families missing from sitemap',
    'candidate_public_match=yes',
    'unknown taxonomy hub returned',
    'BreadcrumbList JSON-LD missing',
    'CollectionPage JSON-LD',
  ]) {
    assert.ok(
      source.includes(required),
      `missing verifier safeguard: ${required}`
    );
  }

  assert.doesNotMatch(
    source,
    /taxonomy_hubs\s*==\s*25/
  );

  const result = spawnSync(
    'python3',
    [
      new URL(
        '../scripts/verify-production-release.py',
        import.meta.url
      ).pathname,
      'self-test',
    ],
    {
      encoding: 'utf8',
    }
  );

  assert.equal(
    result.status,
    0,
    result.stderr || result.stdout
  );
  assert.match(
    result.stdout,
    /production_release_verifier_self_test=passed/
  );
});

test('permanent production deploy script has valid Bash syntax', () => {
  const result = spawnSync(
    'bash',
    [
      '-n',
      new URL(
        '../scripts/deploy-production.sh',
        import.meta.url
      ).pathname,
    ],
    {
      encoding: 'utf8',
    }
  );

  assert.equal(
    result.status,
    0,
    result.stderr || result.stdout
  );
});
