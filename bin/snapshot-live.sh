#!/usr/bin/env bash

set -euo pipefail

SOURCE="/var/www/revelations-cms/public/wp-content/mu-plugins"
REPO="/root/revelations-cms-source"

if [ ! -d "$REPO/.git" ]; then
    echo "Git repository not found: $REPO" >&2
    exit 1
fi

echo "Checking live PHP syntax..."

while IFS= read -r -d '' file; do
    php -l "$file" >/dev/null
done < <(
    find "$SOURCE" \
        -maxdepth 1 \
        -type f \
        -name '*.php' \
        -print0
)

echo "Copying live MU plugins..."

rsync -a --delete \
    --exclude='*.bak' \
    --exclude='*.backup' \
    --exclude='*.before-*' \
    --exclude='*.orig' \
    --exclude='*.rej' \
    --exclude='*~' \
    "$SOURCE/" \
    "$REPO/mu-plugins/"

git -C "$REPO" add -A

if git -C "$REPO" diff --cached --quiet; then
    echo "No source changes to commit."
    exit 0
fi

MESSAGE="${1:-Snapshot live CMS $(date -u '+%Y-%m-%d %H:%M UTC')}"

git -C "$REPO" commit \
    -m "$MESSAGE"

echo
git -C "$REPO" status --short
git -C "$REPO" log -1 --oneline
