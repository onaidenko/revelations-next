#!/usr/bin/env bash
# Source-only transaction module. Used by staging-remote-deploy.sh and offline fixtures.
# Required globals: LIVE CANDIDATE PREVIOUS BACKUP SERVICE OLD_COMMIT OLD_BUILD_ID
# EXPECTED_COMMIT BUILD_ID. Caller supplies staging_verify_new_runtime and
# staging_verify_old_runtime (real HTTP/service checks in remote worker).

SWITCH_REQUESTED=0
SUCCESS=0
CANDIDATE_PID=''

staging_stop_candidate() {
  if [[ -n "${CANDIDATE_PID:-}" ]]; then
    kill -- "-$CANDIDATE_PID" 2>/dev/null || true
    kill "$CANDIDATE_PID" 2>/dev/null || true
    wait "$CANDIDATE_PID" 2>/dev/null || true
    CANDIDATE_PID=''
  fi
}

staging_old_files_ok() {
  [[ -f "$LIVE/RELEASE_COMMIT" && -f "$LIVE/.next/BUILD_ID" ]] &&
    [[ "$(cat "$LIVE/RELEASE_COMMIT")" == "$OLD_COMMIT" ]] &&
    [[ "$(cat "$LIVE/.next/BUILD_ID")" == "$OLD_BUILD_ID" ]]
}

staging_new_files_ok() {
  [[ -f "$LIVE/RELEASE_COMMIT" && -f "$LIVE/.next/BUILD_ID" ]] &&
    [[ "$(cat "$LIVE/RELEASE_COMMIT")" == "$EXPECTED_COMMIT" ]] &&
    [[ "$(cat "$LIVE/.next/BUILD_ID")" == "$BUILD_ID" ]]
}

# Retry bounded post-start readiness checks. A brief 502 while the Node listener
# starts is not, by itself, a failed deployment. Persistent errors remain fatal.
# Tests may reduce the attempt count/delay without changing production defaults.
staging_wait_for_runtime() {
  local phase="$1" verifier="$2"
  local attempts="${STAGING_READINESS_ATTEMPTS:-20}"
  local delay="${STAGING_READINESS_DELAY_SECONDS:-2}"
  local i

  [[ "$attempts" =~ ^[0-9]+$ ]] && (( attempts >= 1 && attempts <= 30 )) || return 2
  [[ "$delay" =~ ^[0-9]+$ ]] && (( delay <= 5 )) || return 2

  for (( i=1; i<=attempts; i++ )); do
    if "$verifier"; then
      echo "STAGING_READINESS_OK phase=$phase attempt=$i/$attempts"
      return 0
    fi
    if (( i < attempts )); then sleep "$delay"; fi
  done

  echo "STAGING_READINESS_TIMEOUT phase=$phase attempts=$attempts" >&2
  return 1
}

staging_rollback() {
  local failed=0
  staging_stop_candidate
  if (( SWITCH_REQUESTED == 0 )); then
    echo 'ROLLBACK_NOT_REQUIRED=live_unmodified'
    return 0
  fi

  # Do not assume any mv completed: a signal may arrive between mv and a flag.
  systemctl stop "$SERVICE" >/dev/null 2>&1 || true
  if [[ -d "$PREVIOUS" ]]; then
    if [[ -e "$LIVE" ]]; then
      mkdir -p "$BACKUP" || failed=1
      if (( failed == 0 )); then
        mv "$LIVE" "$BACKUP/failed-release" || failed=1
      fi
    fi
    if (( failed == 0 )); then
      mv "$PREVIOUS" "$LIVE" || failed=1
    fi
  elif ! staging_old_files_ok; then
    if [[ -e "$LIVE" ]]; then
      mkdir -p "$BACKUP" || failed=1
      if (( failed == 0 )); then
        mv "$LIVE" "$BACKUP/failed-release" || failed=1
      fi
    fi
    if (( failed == 0 )); then
      [[ -d "$BACKUP/live-release" ]] || failed=1
      if (( failed == 0 )); then
        cp -a "$BACKUP/live-release" "$LIVE" || failed=1
      fi
    fi
  fi

  if (( failed == 0 )); then
    staging_old_files_ok || failed=1
  fi
  if (( failed == 0 )); then
    systemctl start "$SERVICE" || failed=1
  fi
  if (( failed == 0 )); then
    staging_wait_for_runtime rollback staging_verify_old_runtime || failed=1
  fi

  if (( failed != 0 )); then
    echo "ROLLBACK_FAILED backup=$BACKUP previous=$PREVIOUS live=$LIVE" >&2
    return 1
  fi
  echo "ROLLBACK_SUCCESS commit=$OLD_COMMIT backup=$BACKUP"
  return 0
}

staging_on_exit() {
  local status=$?
  trap - EXIT HUP INT TERM
  if (( status != 0 && SUCCESS != 1 )); then
    if ! staging_rollback; then
      # Do not mask a failed rollback; retain evidence in BACKUP.
      echo 'DEPLOY_CRITICAL=rollback_unverified' >&2
      status=70
    fi
  fi
  exit "$status"
}

staging_install_traps() {
  trap staging_on_exit EXIT
  trap 'exit 129' HUP
  trap 'exit 130' INT
  trap 'exit 143' TERM
}

staging_switch_transaction() {
  # Backup and candidate verification MUST complete before this function.
  [[ -d "$BACKUP/live-release" && -d "$LIVE" && -d "$CANDIDATE" ]] || return 1
  [[ ! -e "$PREVIOUS" ]] || return 1
  [[ "$(cat "$BACKUP/live-release/RELEASE_COMMIT")" == "$OLD_COMMIT" ]] || return 1
  [[ "$(cat "$BACKUP/live-release/.next/BUILD_ID")" == "$OLD_BUILD_ID" ]] || return 1
  staging_old_files_ok || return 1
  staging_stop_candidate

  # Arm rollback before the first operation that can touch the running release.
  SWITCH_REQUESTED=1
  systemctl stop "$SERVICE" || return 1
  mv "$LIVE" "$PREVIOUS" || return 1
  mv "$CANDIDATE" "$LIVE" || return 1
  systemctl start "$SERVICE" || return 1
  staging_new_files_ok || return 1
  staging_wait_for_runtime new staging_verify_new_runtime || return 1
  SUCCESS=1
  echo "STAGING_SWITCH_SUCCESS commit=$EXPECTED_COMMIT build_id=$BUILD_ID backup=$BACKUP"
}
