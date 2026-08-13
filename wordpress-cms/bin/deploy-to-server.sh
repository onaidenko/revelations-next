#!/usr/bin/env bash

set -euo pipefail

# Canonical production CMS deployment procedure. Full sync is explicit; use
# --files for a bounded targeted release. The SSH alias is defined locally and
# in the protected production environment, never replaced with a raw IP here.
SERVER="revelations-prod"
REMOTE_ROOT="/var/www/revelations-cms/public"
REMOTE_MU="$REMOTE_ROOT/wp-content/mu-plugins"
LOCAL_ROOT="$( cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd )"
LOCAL_MU="$LOCAL_ROOT/mu-plugins"

usage() {
    cat <<USAGE
Usage:
  $0 --dry-run --files plugin-one.php [plugin-two.js ...]
  $0 --deploy --files plugin-one.php [plugin-two.js ...]
  $0 --dry-run --full
  $0 --deploy --full

Exactly one scope is required: --files deploys only an allowlisted set of
direct files from wordpress-cms/mu-plugins; --full performs the explicit full
MU-plugin sync.
USAGE
}

fail() {
    echo "Error: $*" >&2
    exit 1
}

local_sha256() {
    shasum -a 256 "$1" | awk '{print $1}'
}

MODE="${1:-}"
if [ "$MODE" != "--dry-run" ] && [ "$MODE" != "--deploy" ]; then
    usage
    exit 1
fi
shift

TARGETED=0
FILES=()
SCOPE="${1:-}"
case "$SCOPE" in
    --files)
        TARGETED=1
        shift
        [ "$#" -gt 0 ] || fail "Targeted mode requires at least one MU-plugin file."
        FILES=( "$@" )
        ;;
    --full)
        shift
        [ "$#" -eq 0 ] || fail "--full cannot be combined with --files or other arguments."
        ;;
    '')
        fail "Exactly one deployment scope is required: --files or --full."
        ;;
    *)
        fail "Expected exactly one deployment scope: --files or --full."
        ;;
esac

[ -d "$LOCAL_MU" ] || fail "Local MU plugins directory not found: $LOCAL_MU"

if [ "$TARGETED" -eq 1 ]; then
    PAIRS=()
    for file in "${FILES[@]}"; do
        # Targeted releases deliberately accept only direct plugin filenames.
        # This rejects traversal, directories, bin/docs/tests paths and links.
        [[ "$file" =~ ^[A-Za-z0-9][A-Za-z0-9._-]*\.(php|js)$ ]] || fail "Invalid targeted MU-plugin filename: $file"
        [ -f "$LOCAL_MU/$file" ] && [ ! -L "$LOCAL_MU/$file" ] || fail "Targeted file does not exist as a regular local MU-plugin: $file"
        for seen in "${PAIRS[@]}"; do
            [ "${seen%%=*}" != "$file" ] || fail "Duplicate targeted file: $file"
        done
        PAIRS+=( "$file=$(local_sha256 "$LOCAL_MU/$file")" )
    done
fi

remote_target_report() {
    ssh "$SERVER" bash -s -- "$REMOTE_MU" "${PAIRS[@]}" <<'REMOTE'
set -euo pipefail
REMOTE_MU="$1"
shift
test -d "$REMOTE_MU"
printf 'Remote MU target: %s\n' "$REMOTE_MU"
for pair in "$@"; do
    file="${pair%%=*}"
    expected="${pair#*=}"
    live="$REMOTE_MU/$file"
    if [ -e "$live" ]; then
        actual="$(sha256sum "$live" | awk '{print $1}')"
        if [ "$actual" = "$expected" ]; then state="unchanged"; else state="changed"; fi
        printf '%s | %s | live %s | local %s\n' "$file" "$state" "$actual" "$expected"
    else
        printf '%s | new | local %s\n' "$file" "$expected"
    fi
done
REMOTE
}

targeted_dry_run() {
    echo "===== TARGETED CMS DRY RUN ====="
    echo "Local MU source: $LOCAL_MU"
    echo "Remote MU target: $REMOTE_MU"
    echo "Allowlisted files:"
    for pair in "${PAIRS[@]}"; do
        printf '  %s | SHA-256 %s\n' "${pair%%=*}" "${pair#*=}"
    done
    echo
    echo "===== REMOTE TARGET AND CHECKSUM COMPARISON ====="
    remote_target_report
    echo
    echo "===== PLANNED SAFEGUARDS ====="
    echo "- Stage only allowlisted files in a unique remote /tmp directory."
    echo "- Verify staged SHA-256 values and run php -l on staged PHP copies."
    echo "- Back up only existing allowlisted live files; record new files in a manifest."
    echo "- Install only allowlisted files, then set root:www-data and mode 640."
    echo "- Verify live SHA-256, php -l for targeted PHP files, and existing CMS sanity checks."
    echo "- Targeted mode never uses rsync or --delete."
    echo
    echo "Dry run complete. No remote files were staged or changed."
}

targeted_deploy() {
    local stage_dir stamp backup_dir
    echo "===== TARGETED CMS DEPLOY ====="
    stage_dir="$(ssh "$SERVER" "set -eu; test -d '$REMOTE_MU'; mktemp -d /tmp/revelations-cms-targeted.XXXXXX")" || fail "Could not create remote targeted staging directory."
    stamp="$(date +%Y%m%d-%H%M%S)-$$"
    backup_dir="/root/revelations-mu-plugins-targeted-before-deploy-$stamp"
    printf 'Remote staging directory: %s\n' "$stage_dir"

    for pair in "${PAIRS[@]}"; do
        file="${pair%%=*}"
        if ! scp "$LOCAL_MU/$file" "$SERVER:$stage_dir/$file"; then
            ssh "$SERVER" "rm -rf -- '$stage_dir'" || true
            fail "Could not stage targeted file: $file"
        fi
    done

    echo "===== STAGED PHP SYNTAX AND CHECKSUM VERIFICATION ====="
    if ! ssh "$SERVER" bash -s -- "$stage_dir" "$REMOTE_MU" "${PAIRS[@]}" <<'REMOTE'
set -euo pipefail
STAGE_DIR="$1"
REMOTE_MU="$2"
shift 2
cleanup_on_failure() { rm -rf -- "$STAGE_DIR"; }
trap 'status=$?; if [ "$status" -ne 0 ]; then cleanup_on_failure; fi; exit "$status"' EXIT
test -d "$STAGE_DIR"
for pair in "$@"; do
    file="${pair%%=*}"
    expected="${pair#*=}"
    staged="$STAGE_DIR/$file"
    test -f "$staged"
    actual="$(sha256sum "$staged" | awk '{print $1}')"
    test "$actual" = "$expected"
    if [ -e "$REMOTE_MU/$file" ]; then
        printf 'current live SHA-256: %s | %s\n' "$file" "$(sha256sum "$REMOTE_MU/$file" | awk '{print $1}')"
    else
        printf 'current live state: %s | new\n' "$file"
    fi
    case "$file" in
        *.php) php -l "$staged" >/dev/null ;;
    esac
    printf 'staged OK: %s\n' "$file"
done
REMOTE
    then
        fail "Staged checksum or PHP syntax verification failed; live MU plugins were not changed."
    fi

    echo "===== TARGETED BACKUP, INSTALL AND VERIFICATION ====="
    if ! ssh "$SERVER" bash -s -- "$stage_dir" "$REMOTE_ROOT" "$REMOTE_MU" "$stamp" "${PAIRS[@]}" <<'REMOTE'
set -euo pipefail
STAGE_DIR="$1"
REMOTE_ROOT="$2"
REMOTE_MU="$3"
STAMP="$4"
shift 4
BACKUP_DIR="/root/revelations-mu-plugins-targeted-before-deploy-$STAMP"

cleanup() { rm -rf -- "$STAGE_DIR"; }
trap cleanup EXIT
test -d "$STAGE_DIR"
test -d "$REMOTE_MU"
mkdir -p "$BACKUP_DIR/files"
MANIFEST="$BACKUP_DIR/manifest.tsv"
printf 'file\tprevious_state\tsha256\n' > "$MANIFEST"

for pair in "$@"; do
    file="${pair%%=*}"
    live="$REMOTE_MU/$file"
    if [ -e "$live" ]; then
        cp -p -- "$live" "$BACKUP_DIR/files/$file"
        printf '%s\texisting\t%s\n' "$file" "$(sha256sum "$live" | awk '{print $1}')" >> "$MANIFEST"
    else
        printf '%s\tnew\t-\n' "$file" >> "$MANIFEST"
    fi
done
printf 'Targeted backup: %s\n' "$BACKUP_DIR"
printf 'Rollback manifest: %s\n' "$MANIFEST"

for pair in "$@"; do
    file="${pair%%=*}"
    expected="${pair#*=}"
    staged="$STAGE_DIR/$file"
    live="$REMOTE_MU/$file"
    temporary="$REMOTE_MU/.${file}.revelations-targeted-$STAMP"
    test "$(sha256sum "$staged" | awk '{print $1}')" = "$expected"
    install -o root -g www-data -m 640 "$staged" "$temporary"
    mv -f -- "$temporary" "$live"
    test "$(sha256sum "$live" | awk '{print $1}')" = "$expected"
    case "$file" in
        *.php) php -l "$live" >/dev/null ;;
    esac
    test "$(stat -c '%U:%G' "$live")" = "root:www-data"
    test "$(stat -c '%a' "$live")" = "640"
    printf 'live OK: %s\n' "$file"
done

sudo -u revelations-cms -- wp --path="$REMOTE_ROOT" eval '
echo wp_json_encode([
    "wordpress_loaded" => function_exists("get_post"),
    "publish_gate_loaded" => function_exists("revelations_editorial_publish_gate_reason"),
    "editor_readiness_loaded" => function_exists("revelations_cms_editor_data"),
    "draft_178_status" => get_post_status(178),
    "draft_214_status" => get_post_status(214),
    "articles_published_by_deploy" => 0,
    "database_changes_by_deploy" => 0,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
'

HTTP_CODE="$(curl -sS -o /dev/null -w "%{http_code}" https://cms.revelations.me/wp-login.php)"
printf 'CMS HTTP: %s\n' "$HTTP_CODE"
REMOTE
    then
        echo "Targeted deployment failed. Recovery backup: $backup_dir" >&2
        echo "Rollback manifest: $backup_dir/manifest.tsv" >&2
        exit 1
    fi
    echo "Targeted deployment complete."
}

full_deploy() {
    echo "===== LOCAL SOURCE ====="
    echo "$LOCAL_MU"
    echo
    echo "===== LOCAL FILE CHECK ====="
    find "$LOCAL_MU" -maxdepth 1 -type f \( -name '*.php' -o -name '*.js' \) -print | sort

    echo
    echo "===== REMOTE PHP SYNTAX CHECK ====="
    ssh "$SERVER" bash -s -- "$REMOTE_MU" <<'REMOTE'
set -euo pipefail
REMOTE_MU="$1"
while IFS= read -r -d '' FILE; do php -l "$FILE" >/dev/null; done < <( find "$REMOTE_MU" -maxdepth 1 -type f -name '*.php' -print0 )
echo "Current live PHP syntax: OK"
REMOTE

    echo
    echo "===== RSYNC COMPARISON ====="
    RSYNC_ARGS=( -rvc --delete-delay --itemize-changes --exclude='.DS_Store' --exclude='*.bak' --exclude='*.backup' --exclude='*.before-*' --exclude='*.orig' --exclude='*.rej' --exclude='*~' )
    if [ "$MODE" = "--dry-run" ]; then RSYNC_ARGS+=( --dry-run ); fi

    if [ "$MODE" = "--deploy" ]; then
        STAMP="$(date +%Y%m%d-%H%M%S)"
        echo
        echo "===== CREATE REMOTE BACKUP ====="
        ssh "$SERVER" bash -s -- "$REMOTE_ROOT" "$STAMP" <<'REMOTE'
set -euo pipefail
REMOTE_ROOT="$1"
STAMP="$2"
BACKUP="/root/revelations-mu-plugins-before-deploy-$STAMP.tar.gz"
tar -czf "$BACKUP" -C "$REMOTE_ROOT/wp-content" mu-plugins
ls -lh "$BACKUP"
REMOTE
    fi

    rsync "${RSYNC_ARGS[@]}" "$LOCAL_MU/" "$SERVER:$REMOTE_MU/"
    if [ "$MODE" = "--dry-run" ]; then
        echo
        echo "Dry run complete. No server files were changed."
        return
    fi

    echo
    echo "===== NORMALIZE REMOTE PERMISSIONS ====="
    ssh "$SERVER" bash -s -- "$REMOTE_MU" <<'REMOTE'
set -euo pipefail
REMOTE_MU="$1"
chown root:www-data "$REMOTE_MU"
chmod 750 "$REMOTE_MU"
while IFS= read -r -d '' FILE; do chown root:www-data "$FILE"; chmod 640 "$FILE"; done < <( find "$REMOTE_MU" -maxdepth 1 -type f \( -name '*.php' -o -name '*.js' \) -print0 )
while IFS= read -r -d '' FILE; do
    sudo -u revelations-cms -- test -r "$FILE"
    test "$(stat -c '%U:%G' "$FILE")" = "root:www-data"
    test "$(stat -c '%a' "$FILE")" = "640"
done < <( find "$REMOTE_MU" -maxdepth 1 -type f \( -name '*.php' -o -name '*.js' \) -print0 )
echo "Remote ownership and permissions: OK"
REMOTE

    echo
    echo "===== VERIFY DEPLOYED FILES ====="
    ssh "$SERVER" bash -s -- "$REMOTE_ROOT" <<'REMOTE'
set -euo pipefail
REMOTE_ROOT="$1"
REMOTE_MU="$REMOTE_ROOT/wp-content/mu-plugins"
while IFS= read -r -d '' FILE; do php -l "$FILE" >/dev/null; done < <( find "$REMOTE_MU" -maxdepth 1 -type f -name '*.php' -print0 )
sudo -u revelations-cms -- wp --path="$REMOTE_ROOT" eval '
echo wp_json_encode([
    "wordpress_loaded" => function_exists("get_post"),
    "publish_gate_loaded" => function_exists("revelations_editorial_publish_gate_reason"),
    "editor_readiness_loaded" => function_exists("revelations_cms_editor_data"),
    "draft_178_status" => get_post_status(178),
    "draft_214_status" => get_post_status(214),
    "articles_published_by_deploy" => 0,
    "database_changes_by_deploy" => 0,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
'
HTTP_CODE="$(curl -sS -o /dev/null -w "%{http_code}" https://cms.revelations.me/wp-login.php)"
echo "CMS HTTP: $HTTP_CODE"
REMOTE
    echo
    echo "Deployment complete."
}

if [ "$TARGETED" -eq 1 ]; then
    if [ "$MODE" = "--dry-run" ]; then targeted_dry_run; else targeted_deploy; fi
else
    full_deploy
fi
