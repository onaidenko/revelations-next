import assert from 'node:assert/strict';
import { mkdir, mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';

const deployPath = new URL(
  '../scripts/deploy-production.sh',
  import.meta.url
);
const preparePath = new URL(
  '../scripts/prepare-production-release.sh',
  import.meta.url
);
const verifierPath = new URL(
  '../scripts/verify-production-release.py',
  import.meta.url
);
const workflowPath = new URL(
  '../.github/workflows/production-deploy.yml',
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
    '--prepared-release',
    '--validate-prepared-release',
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
    'UPLOAD_VERIFIED',
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
    'PREPARED_RELEASE="$(cd "$PREPARED_RELEASE" && pwd)"',
    'manifest.json',
    'Prepared artifact contains environment or key files.',
    'Prepared artifact contains the staging domain.',
    'REMOTE_TMP_CREATED=1',
    'PREPARED_RELEASE_VALIDATION_SUCCESS',
    "trap 'exit 129' HUP",
    "trap 'exit 130' INT",
    "trap 'exit 143' TERM",
    'SERVICE_STOPPING=1',
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
  assert.doesNotMatch(source, /npm run build:production/);
  assert.doesNotMatch(source, /npm test/);
  assert.doesNotMatch(source, /npm run lint/);
  assert.doesNotMatch(source, /\breadarray\b/);
  assert.doesNotMatch(source, /\bmapfile\b/);
  assert.ok(
    source.indexOf('===== PREPARED RELEASE VALIDATION =====') <
      source.indexOf('UPLOAD_START'),
    'prepared artifact validation must complete before upload'
  );
  assert.ok(
    source.indexOf('UPLOAD_VERIFIED') < source.indexOf('REMOTE_DEPLOY_START'),
    'remote candidate deployment must wait for upload verification'
  );
  assert.match(
    source,
    /test -f '\$REMOTE_TMP\/\$\(basename "\$ARCHIVE"\)'[\s\S]*test -f '\$REMOTE_TMP\/\$\(basename "\$VERIFY_SOURCE"\)'[\s\S]*sha256sum/
  );
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

test('prepare and deploy scripts keep the commit-bound release contract', async () => {
  const source = await readFile(preparePath, 'utf8');
  const deploySource = await readFile(deployPath, 'utf8');

  for (const required of [
    '.release/production',
    'test ! -e "$RELEASE_DIR"',
    'manifest.json',
    '"schema_version": 1',
    '"expected_commit": commit',
    '"archive_sha256": archive_sha',
    '"staging_domain": "absent"',
    '"runtime_secret_files": "absent"',
    'npm test',
    'npm run lint',
    'npm run audit:taxonomy',
    'npm run build:production',
    'PREPARE_SUCCESS',
    'DEFAULT_RELEASE_BRANCH="admin-editorial"',
    'APPROVED_RELEASE_BRANCH="release/ga4-consent-legal"',
    '--release-branch',
    'Release branch is not allowlisted',
  ]) {
    assert.ok(source.includes(required), `missing prepare safeguard: ${required}`);
  }

  assert.doesNotMatch(source, /\bssh\b/);
  assert.doesNotMatch(source, /\bscp\b/);
  assert.doesNotMatch(source, /\breadarray\b/);
  assert.doesNotMatch(source, /\bmapfile\b/);
  assert.match(deploySource, /assert data\.get\("expected_commit"\) == expected_commit/);
  assert.match(deploySource, /shasum -a 256 "\$ARCHIVE"/);
  assert.match(deploySource, /assert data\.get\("cms_api_url"\) == cms_api_url/);
  assert.match(deploySource, /assert checks\.get\("staging_domain"\) == "absent"/);
  assert.match(deploySource, /assert checks\.get\("runtime_secret_files"\) == "absent"/);
});

test('production workflow is bound to the approved GA4 release branch and its exact dispatch SHA', async () => {
  const source = await readFile(workflowPath, 'utf8');

  for (const required of [
    'workflow_dispatch:',
    'environment: production',
    'RELEASE_BRANCH: release/ga4-consent-legal',
    'EXPECTED_COMMIT: ${{ inputs.commit }}',
    'ref: ${{ env.RELEASE_BRANCH }}',
    'if [[ ! "$EXPECTED_COMMIT" =~ ^[0-9a-f]{40}$ ]]',
    'test "$GITHUB_REF" = "refs/heads/$RELEASE_BRANCH"',
    'test "$EXPECTED_COMMIT" = "$GITHUB_SHA"',
    'git fetch origin "$RELEASE_BRANCH"',
    'git switch --force-create "$RELEASE_BRANCH" "origin/$RELEASE_BRANCH"',
    'test "$(git branch --show-current)" = "$RELEASE_BRANCH"',
    'test "$EXPECTED_COMMIT" = "$(git rev-parse HEAD)"',
    'test "$EXPECTED_COMMIT" = "$(git rev-parse "origin/$RELEASE_BRANCH")"',
    '--release-branch "$RELEASE_BRANCH"',
    '--confirm "deploy-production-${COMMIT:0:12}"',
  ]) {
    assert.ok(source.includes(required), `missing workflow safeguard: ${required}`);
  }

  assert.doesNotMatch(source, /ref: admin-editorial/);
  assert.doesNotMatch(source, /ref: \$\{\{ inputs\.commit \}\}/);
  assert.doesNotMatch(source, /COMMIT="\$\{\{ inputs\.commit \}\}"/);
  assert.doesNotMatch(source, /merge-base --is-ancestor/);
});

test('release-branch checkout stays attached and rejects a branch advanced after dispatch', async () => {
  const fixture = await mkdtemp(join(tmpdir(), 'revelations-workflow-checkout-'));
  const remote = join(fixture, 'remote.git');
  const source = join(fixture, 'source');
  const runner = join(fixture, 'runner');
  const branch = 'release/ga4-consent-legal';
  const git = (cwd, args) => {
    const result = spawnSync('git', ['-C', cwd, ...args], {
      encoding: 'utf8',
    });
    assert.equal(result.status, 0, result.stderr || result.stdout);
    return result.stdout.trim();
  };

  try {
    assert.equal(
      spawnSync('git', ['init', '--bare', remote], { encoding: 'utf8' }).status,
      0
    );
    assert.equal(
      spawnSync('git', ['init', source], { encoding: 'utf8' }).status,
      0
    );
    git(source, ['checkout', '-b', branch]);
    git(source, ['config', 'user.email', 'release@example.test']);
    git(source, ['config', 'user.name', 'Release Test']);
    await writeFile(join(source, 'RELEASE_COMMIT'), 'first\n');
    git(source, ['add', 'RELEASE_COMMIT']);
    git(source, ['commit', '-m', 'first']);
    git(source, ['remote', 'add', 'origin', remote]);
    git(source, ['push', '-u', 'origin', branch]);
    const expectedCommit = git(source, ['rev-parse', 'HEAD']);

    assert.equal(
      spawnSync('git', ['init', runner], { encoding: 'utf8' }).status,
      0
    );
    git(runner, ['remote', 'add', 'origin', remote]);
    git(runner, ['fetch', 'origin', branch]);
    git(runner, ['checkout', '--detach', expectedCommit]);

    const attach = spawnSync(
      'bash',
      [
        '-c',
        'set -euo pipefail; git fetch origin "$1"; git switch --force-create "$1" "origin/$1"; test "$(git branch --show-current)" = "$1"; test "$2" = "$(git rev-parse HEAD)"',
        'bash',
        branch,
        expectedCommit,
      ],
      { cwd: runner, encoding: 'utf8' }
    );
    assert.equal(attach.status, 0, attach.stderr || attach.stdout);
    assert.equal(git(runner, ['branch', '--show-current']), branch);

    await writeFile(join(source, 'RELEASE_COMMIT'), 'second\n');
    git(source, ['add', 'RELEASE_COMMIT']);
    git(source, ['commit', '-m', 'second']);
    git(source, ['push', 'origin', branch]);

    const advanced = spawnSync(
      'bash',
      [
        '-c',
        'set -euo pipefail; git fetch origin "$1"; git switch --force-create "$1" "origin/$1"; test "$2" = "$(git rev-parse HEAD)"',
        'bash',
        branch,
        expectedCommit,
      ],
      { cwd: runner, encoding: 'utf8' }
    );
    assert.notEqual(advanced.status, 0, advanced.stderr || advanced.stdout);
  } finally {
    await rm(fixture, { recursive: true, force: true });
  }
});

test('production release scripts reject an unallowlisted branch before any release work', () => {
  for (const script of [preparePath, deployPath]) {
    const result = spawnSync(
      'bash',
      [
        script.pathname,
        '--expected-commit',
        'fd3e875a753b0d1ccf511fb46f79b0af968c8c73',
        '--release-branch',
        'release/untrusted',
      ],
      { encoding: 'utf8' }
    );

    assert.equal(result.status, 2, result.stderr || result.stdout);
    assert.match(result.stderr, /Release branch is not allowlisted/);
  }
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


test('failed candidate activation restores the previous live release via the real rollback trap', async () => {
  const source = await readFile(deployPath, 'utf8');
  const switchStart = source.indexOf(
    '\nmv "$LIVE_ROOT" "$PREVIOUS"',
    source.indexOf('echo "ATOMIC_SWITCH_START"')
  );
  const switchEnd = source.indexOf('\nfind_service_ports()', switchStart);
  const rollbackStart = source.indexOf('\nrollback() {');
  const onExitStart = source.indexOf('\non_exit() {', rollbackStart);
  const trapStart = source.indexOf('\ntrap on_exit EXIT', onExitStart);

  assert.ok(switchStart > 0 && switchEnd > switchStart);
  assert.ok(rollbackStart > 0 && onExitStart > rollbackStart && trapStart > onExitStart);

  const switchSource = source.slice(switchStart + 1, switchEnd).trim();
  const rollbackSource = source.slice(rollbackStart + 1, onExitStart).trim();
  const onExitSource = source.slice(onExitStart + 1, trapStart).trim();

  const fixture = await mkdtemp(join(tmpdir(), 'revelations-rollback-'));

  try {
    await mkdir(join(fixture, 'live'), { recursive: true });
    await mkdir(join(fixture, 'candidate'), { recursive: true });
    await mkdir(join(fixture, 'backup'), { recursive: true });
    await writeFile(join(fixture, 'live', 'RELEASE_COMMIT'), 'previous-release');

    const shell = [
      'set -euo pipefail',
      'LIVE_ROOT="$1/live"',
      'PREVIOUS="$1/previous"',
      'CANDIDATE="$1/candidate"',
      'BACKUP="$1/backup"',
      'FAILED_RELEASE="$BACKUP/failed-release"',
      'PREDEPLOY_MANIFEST="$BACKUP/predeploy-manifest.json"',
      'SITE_URL="https://example.test"',
      'SERVICE="test-service"',
      'SWITCHED=0',
      'SUCCESS=0',
      'CANDIDATE_PID=""',
      'stop_candidate() { :; }',
      'systemctl() { :; }',
      'verify_with_retries() { :; }',
      'mv() {',
      '  if [[ "$1" == "$CANDIDATE" && "$2" == "$LIVE_ROOT" ]]; then return 41; fi',
      '  command mv "$@"',
      '}',
      'fail() { printf "INJECTED_FAILURE=%s\\n" "$1" >&2; exit 12; }',
      rollbackSource,
      onExitSource,
      'trap on_exit EXIT',
      switchSource,
    ].join('\n');

    const result = spawnSync('bash', ['-c', shell, 'bash', fixture], {
      encoding: 'utf8',
    });

    assert.equal(result.status, 12, result.stderr);
    assert.match(result.stderr, /INJECTED_FAILURE=candidate_activation_failed/);
    assert.equal(
      await readFile(join(fixture, 'live', 'RELEASE_COMMIT'), 'utf8'),
      'previous-release',
      'rollback must restore the original live directory after the second mv fails'
    );
  } finally {
    await rm(fixture, { recursive: true, force: true });
  }
});

test('failed live release relocation restarts the unchanged live release via the real rollback trap', async () => {
  const source = await readFile(deployPath, 'utf8');
  const switchStart = source.indexOf(
    '\nmv "$LIVE_ROOT" "$PREVIOUS"',
    source.indexOf('echo "ATOMIC_SWITCH_START"')
  );
  const switchEnd = source.indexOf('\nfind_service_ports()', switchStart);
  const rollbackStart = source.indexOf('\nrollback() {');
  const onExitStart = source.indexOf('\non_exit() {', rollbackStart);
  const trapStart = source.indexOf('\ntrap on_exit EXIT', onExitStart);

  assert.ok(switchStart > 0 && switchEnd > switchStart);
  assert.ok(rollbackStart > 0 && onExitStart > rollbackStart && trapStart > onExitStart);

  const switchSource = source.slice(switchStart + 1, switchEnd).trim();
  const rollbackSource = source.slice(rollbackStart + 1, onExitStart).trim();
  const onExitSource = source.slice(onExitStart + 1, trapStart).trim();
  const fixture = await mkdtemp(join(tmpdir(), 'revelations-rollback-'));

  try {
    await mkdir(join(fixture, 'live'), { recursive: true });
    await mkdir(join(fixture, 'candidate'), { recursive: true });
    await mkdir(join(fixture, 'backup'), { recursive: true });
    await writeFile(join(fixture, 'live', 'RELEASE_COMMIT'), 'previous-release');

    const shell = [
      'set -euo pipefail',
      'LIVE_ROOT="$1/live"',
      'PREVIOUS="$1/previous"',
      'CANDIDATE="$1/candidate"',
      'BACKUP="$1/backup"',
      'FAILED_RELEASE="$BACKUP/failed-release"',
      'PREDEPLOY_MANIFEST="$BACKUP/predeploy-manifest.json"',
      'SITE_URL="https://example.test"',
      'SERVICE="test-service"',
      'SERVICE_LOG="$1/service.log"',
      'SERVICE_STOPPING=1',
      'SWITCHED=0',
      'SUCCESS=0',
      'CANDIDATE_PID=""',
      'stop_candidate() { :; }',
      'systemctl() { printf "%s\\n" "$*" >> "$SERVICE_LOG"; }',
      'verify_with_retries() { :; }',
      'mv() {',
      '  if [[ "$1" == "$LIVE_ROOT" && "$2" == "$PREVIOUS" ]]; then return 40; fi',
      '  command mv "$@"',
      '}',
      'fail() { printf "INJECTED_FAILURE=%s\\n" "$1" >&2; exit 12; }',
      rollbackSource,
      onExitSource,
      'trap on_exit EXIT',
      switchSource,
    ].join('\n');

    const result = spawnSync('bash', ['-c', shell, 'bash', fixture], {
      encoding: 'utf8',
    });

    assert.equal(result.status, 12, result.stderr);
    assert.match(result.stderr, /INJECTED_FAILURE=live_release_move_failed/);
    assert.equal(
      await readFile(join(fixture, 'live', 'RELEASE_COMMIT'), 'utf8'),
      'previous-release',
      'a failed first mv must leave the live directory in place'
    );
    assert.match(
      await readFile(join(fixture, 'service.log'), 'utf8'),
      /start test-service/,
      'rollback must restart the service after a failed first mv'
    );
  } finally {
    await rm(fixture, { recursive: true, force: true });
  }
});
