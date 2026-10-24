import assert from 'node:assert/strict';
import { mkdtemp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const modulePath = resolve(fileURLToPath(new URL('../scripts/staging-switch-transaction.sh', import.meta.url)));
const oldCommit = '99f99b85c977b4de777dc82f12aae6c067d7f654';
const newCommit = 'a77f2536e47542d2dba640b94326f4977457d7bb';

async function mkRelease(root, marker, build) {
  await mkdir(join(root, '.next'), { recursive: true });
  await writeFile(join(root, 'RELEASE_COMMIT'), marker + '\n');
  await writeFile(join(root, '.next', 'BUILD_ID'), build + '\n');
}

async function scenario(failure) {
  const tmp = await mkdtemp(join(tmpdir(), 'revelations-staging-fixture-'));
  const live = join(tmp, 'live');
  const candidate = join(tmp, 'candidate');
  const previous = join(tmp, 'previous');
  const backup = join(tmp, 'backup');
  const service = join(tmp, 'service-state');
  await mkRelease(live, oldCommit, 'old-build');
  await mkRelease(candidate, newCommit, 'new-build');
  await mkRelease(join(backup, 'live-release'), oldCommit, 'old-build');
  await writeFile(service, 'active\n');

  const shell = `set -euo pipefail
LIVE="$1"; CANDIDATE="$2"; PREVIOUS="$3"; BACKUP="$4"; MOCK_SERVICE="$5"
SERVICE=revelations-staging.service
OLD_COMMIT=${oldCommit}; OLD_BUILD_ID=old-build
EXPECTED_COMMIT=${newCommit}; BUILD_ID=new-build
FAILURE="$6"
STAGING_READINESS_ATTEMPTS=4
STAGING_READINESS_DELAY_SECONDS=0
NEW_PROBES=0; OLD_PROBES=0
source "$7"
systemctl() {
  case "$1" in
    stop)
      if [[ "$FAILURE" == stop-service && "$(cat "$LIVE/RELEASE_COMMIT")" == "$OLD_COMMIT" ]]; then return 1; fi
      printf 'stopped\\n' > "$MOCK_SERVICE" ;;
    start)
      if [[ "$FAILURE" == start-new && "$(cat "$LIVE/RELEASE_COMMIT")" == "$EXPECTED_COMMIT" ]]; then return 1; fi
      if [[ "$FAILURE" == rollback-start && "$(cat "$LIVE/RELEASE_COMMIT")" == "$OLD_COMMIT" ]]; then return 1; fi
      printf 'active\\n' > "$MOCK_SERVICE" ;;
    is-active) [[ "$(cat "$MOCK_SERVICE")" == active ]]; return $? ;;
    *) return 1 ;;
  esac
}
mv() {
  if [[ "$FAILURE" == first-move && "$1" == "$LIVE" && "$2" == "$PREVIOUS" ]]; then return 1; fi
  if [[ "$FAILURE" == second-move && "$1" == "$CANDIDATE" && "$2" == "$LIVE" ]]; then return 1; fi
  if [[ "$FAILURE" == rollback-move && "$1" == "$PREVIOUS" && "$2" == "$LIVE" ]]; then return 1; fi
  command mv "$@"
}
staging_verify_old_runtime() {
  OLD_PROBES=$((OLD_PROBES + 1))
  if [[ "$FAILURE" == delayed-rollback-ready && "$OLD_PROBES" -le 2 ]]; then return 1; fi
  systemctl is-active --quiet "$SERVICE" && staging_old_files_ok
}
staging_verify_new_runtime() {
  NEW_PROBES=$((NEW_PROBES + 1))
  if [[ "$FAILURE" == delayed-new-ready && "$NEW_PROBES" -le 2 ]]; then return 1; fi
  [[ "$FAILURE" != post-verify && "$FAILURE" != delayed-rollback-ready && "$FAILURE" != rollback-move && "$FAILURE" != rollback-start ]] &&
    systemctl is-active --quiet "$SERVICE" && staging_new_files_ok
}
staging_install_traps
if [[ "$FAILURE" == candidate-health ]]; then false; fi
staging_switch_transaction
`;
  const result = spawnSync('bash', ['-c', shell, 'bash', live, candidate, previous, backup, service, failure, modulePath], {encoding:'utf8'});
  const liveMarker = await readFile(join(live, 'RELEASE_COMMIT'), 'utf8').catch(() => null);
  const state = await readFile(service, 'utf8');
  const previousMarker = await readFile(join(previous, 'RELEASE_COMMIT'), 'utf8').catch(() => null);
  const failedMarker = await readFile(join(backup,'failed-release','RELEASE_COMMIT'),'utf8').catch(() => null);
  await rm(tmp, {recursive:true,force:true});
  return { ...result, liveMarker:liveMarker?.trim(), state:state.trim(), previousMarker:previousMarker?.trim(), failedMarker:failedMarker?.trim() };
}

for (const name of ['candidate-health', 'stop-service', 'first-move', 'second-move', 'start-new', 'post-verify']) {
  test(`failure scenario ${name} preserves/restores old staging`, async () => {
    const r = await scenario(name);
    assert.notEqual(r.status, 0, r.stdout + '\n' + r.stderr);
    assert.equal(r.liveMarker, oldCommit, r.stdout + '\n' + r.stderr);
    assert.equal(r.state, 'active', r.stdout + '\n' + r.stderr);
    assert.doesNotMatch(r.stdout + r.stderr, /ROLLBACK_FAILED/);
  });
}

test('successful switch activates new release and retains displaced previous', async () => {
  const r = await scenario('success');
  assert.equal(r.status, 0, r.stdout + '\n' + r.stderr);
  assert.equal(r.liveMarker, newCommit);
  assert.equal(r.previousMarker, oldCommit);
  assert.equal(r.state, 'active');
  assert.match(r.stdout, /STAGING_SWITCH_SUCCESS/);
});

test('failed rollback exits critically and retains backup', async () => {
  const r = await scenario('rollback-move');
  assert.notEqual(r.status, 0);
  assert.equal(r.status, 70);
  assert.match(r.stderr, /ROLLBACK_FAILED/);
  assert.match(r.stderr, /DEPLOY_CRITICAL=rollback_unverified/);
  assert.equal(r.previousMarker, oldCommit);
  assert.equal(r.failedMarker, newCommit);
});

// A real service can be active before its socket accepts HTTP. Prove that
// transient startup failures do not trigger unnecessary rollback.
test('new release survives transient HTTP readiness failures', async () => {
  const r = await scenario('delayed-new-ready');
  assert.equal(r.status, 0, r.stdout + '\n' + r.stderr);
  assert.equal(r.liveMarker, newCommit);
  assert.match(r.stdout, /STAGING_READINESS_OK phase=new attempt=3\/4/);
});

test('rollback survives transient HTTP readiness failures', async () => {
  const r = await scenario('delayed-rollback-ready');
  assert.notEqual(r.status, 0);
  assert.equal(r.liveMarker, oldCommit);
  assert.equal(r.state, 'active');
  assert.match(r.stdout, /STAGING_READINESS_OK phase=rollback attempt=3\/4/);
  assert.match(r.stdout, /ROLLBACK_SUCCESS/);
  assert.doesNotMatch(r.stderr, /ROLLBACK_FAILED/);
});

test('persistent readiness failure times out then restores old release', async () => {
  const r = await scenario('post-verify');
  assert.notEqual(r.status, 0);
  assert.equal(r.liveMarker, oldCommit);
  assert.match(r.stderr, /STAGING_READINESS_TIMEOUT phase=new attempts=4/);
  assert.match(r.stdout, /ROLLBACK_SUCCESS/);
});
