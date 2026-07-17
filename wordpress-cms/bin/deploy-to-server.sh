#!/usr/bin/env bash

set -euo pipefail

SERVER="root@192.248.179.164"
REMOTE_ROOT="/var/www/revelations-cms/public"
REMOTE_MU="$REMOTE_ROOT/wp-content/mu-plugins"

LOCAL_ROOT="$(
    cd "$(dirname "${BASH_SOURCE[0]}")/.."
    pwd
)"

LOCAL_MU="$LOCAL_ROOT/mu-plugins"

MODE="${1:-}"

if [ "$MODE" != "--dry-run" ] && [ "$MODE" != "--deploy" ]; then
    echo "Usage:"
    echo "  $0 --dry-run"
    echo "  $0 --deploy"
    exit 1
fi

if [ ! -d "$LOCAL_MU" ]; then
    echo "Local MU plugins directory not found:"
    echo "$LOCAL_MU"
    exit 1
fi

echo "===== LOCAL SOURCE ====="
echo "$LOCAL_MU"

echo
echo "===== LOCAL FILE CHECK ====="

find "$LOCAL_MU" \
    -maxdepth 1 \
    -type f \
    \( -name '*.php' -o -name '*.js' \) \
    -print \
| sort

echo
echo "===== REMOTE PHP SYNTAX CHECK ====="

ssh "$SERVER" bash -s -- "$REMOTE_MU" <<'REMOTE'
set -euo pipefail

REMOTE_MU="$1"

while IFS= read -r -d '' FILE; do
    php -l "$FILE" >/dev/null
done < <(
    find "$REMOTE_MU" \
        -maxdepth 1 \
        -type f \
        -name '*.php' \
        -print0
)

echo "Current live PHP syntax: OK"
REMOTE

echo
echo "===== RSYNC COMPARISON ====="

RSYNC_ARGS=(
    -rvc
    --delete-delay
    --itemize-changes
    --exclude='.DS_Store'
    --exclude='*.bak'
    --exclude='*.backup'
    --exclude='*.before-*'
    --exclude='*.orig'
    --exclude='*.rej'
    --exclude='*~'
)

if [ "$MODE" = "--dry-run" ]; then
    RSYNC_ARGS+=(--dry-run)
fi

if [ "$MODE" = "--deploy" ]; then
    STAMP="$(date +%Y%m%d-%H%M%S)"

    echo
    echo "===== CREATE REMOTE BACKUP ====="

    ssh "$SERVER" bash -s -- \
        "$REMOTE_ROOT" \
        "$STAMP" <<'REMOTE'
set -euo pipefail

REMOTE_ROOT="$1"
STAMP="$2"

BACKUP="/root/revelations-mu-plugins-before-deploy-$STAMP.tar.gz"

tar -czf "$BACKUP" \
    -C "$REMOTE_ROOT/wp-content" \
    mu-plugins

ls -lh "$BACKUP"
REMOTE
fi

rsync "${RSYNC_ARGS[@]}" \
    "$LOCAL_MU/" \
    "$SERVER:$REMOTE_MU/"

if [ "$MODE" = "--dry-run" ]; then
    echo
    echo "Dry run complete. No server files were changed."
    exit 0
fi

echo
echo "===== NORMALIZE REMOTE PERMISSIONS ====="

ssh "$SERVER" bash -s -- "$REMOTE_MU" <<'REMOTE'
set -euo pipefail

REMOTE_MU="$1"

chown root:www-data "$REMOTE_MU"
chmod 750 "$REMOTE_MU"

while IFS= read -r -d '' FILE; do
    chown root:www-data "$FILE"
    chmod 640 "$FILE"
done < <(
    find "$REMOTE_MU" \
        -maxdepth 1 \
        -type f \
        \( -name '*.php' -o -name '*.js' \) \
        -print0
)

while IFS= read -r -d '' FILE; do
    sudo -u revelations-cms -- test -r "$FILE"

    OWNER_GROUP="$(
        stat -c '%U:%G' "$FILE"
    )"

    MODE="$(
        stat -c '%a' "$FILE"
    )"

    if [ "$OWNER_GROUP" != "root:www-data" ]; then
        echo "Invalid owner/group: $FILE — $OWNER_GROUP"
        exit 1
    fi

    if [ "$MODE" != "640" ]; then
        echo "Invalid file mode: $FILE — $MODE"
        exit 1
    fi
done < <(
    find "$REMOTE_MU" \
        -maxdepth 1 \
        -type f \
        \( -name '*.php' -o -name '*.js' \) \
        -print0
)

echo "Remote ownership and permissions: OK"
REMOTE

echo
echo "===== VERIFY DEPLOYED FILES ====="

ssh "$SERVER" bash -s -- \
    "$REMOTE_ROOT" <<'REMOTE'
set -euo pipefail

REMOTE_ROOT="$1"
REMOTE_MU="$REMOTE_ROOT/wp-content/mu-plugins"

while IFS= read -r -d '' FILE; do
    php -l "$FILE" >/dev/null
done < <(
    find "$REMOTE_MU" \
        -maxdepth 1 \
        -type f \
        -name '*.php' \
        -print0
)

sudo -u revelations-cms -- \
wp --path="$REMOTE_ROOT" eval '
echo wp_json_encode([
    "wordpress_loaded" =>
        function_exists("get_post"),

    "publish_gate_loaded" =>
        function_exists(
            "revelations_editorial_publish_gate_reason"
        ),

    "editor_readiness_loaded" =>
        function_exists(
            "revelations_cms_editor_data"
        ),

    "draft_178_status" =>
        get_post_status(178),

    "draft_214_status" =>
        get_post_status(214),

    "articles_published_by_deploy" => 0,
    "database_changes_by_deploy" => 0,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
. PHP_EOL;
'

HTTP_CODE="$(
    curl -sS \
        -o /dev/null \
        -w "%{http_code}" \
        https://cms.revelations.me/wp-login.php
)"

echo "CMS HTTP: $HTTP_CODE"
REMOTE

echo
echo "Deployment complete."
