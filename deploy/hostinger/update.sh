#!/bin/bash
# =============================================================================
# JobDesk (Demo) – UPDATE an existing deploy on Hostinger shared hosting
# =============================================================================
# Run after pushing to GitHub (main branch):
#   - with SSH:      bash ~/domains/<your-domain>/app/deploy/hostinger/update.sh
#   - without SSH:   create a one-time hPanel cron job with the same command,
#                    read its output, then delete the cron job.
# Nothing secret is printed. .env, storage/ and the database are kept.
# =============================================================================
set -euo pipefail
umask 027

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="${PHP_BIN:-/opt/alt/php85/usr/bin/php}"
COMPOSER="${COMPOSER_BIN:-/usr/local/bin/composer}"
NODE_DIR="${NODE_DIR:-/opt/alt/alt-nodejs24/root/usr/bin}"
# Shared hosting limits threads per account: Vite 8 (Rolldown) stops with
# "global thread pool has not been initialized" unless it uses one thread.
export RAYON_NUM_THREADS="${RAYON_NUM_THREADS:-1}" UV_THREADPOOL_SIZE="${UV_THREADPOOL_SIZE:-2}"
BRANCH="${BRANCH:-main}"

step() { echo "[$(date -u '+%H:%M:%S')] $*"; }

# Everything runs inside main(): bash reads the whole function before starting,
# so "git reset" replacing this file while it runs is safe. After the pull the
# script starts again from the NEW file (phase 2), so changes to this script
# take effect in the same run.
main() {
cd "$APP_DIR"

if [ "${JD_UPDATE_PHASE:-1}" != 2 ]; then
    step "1/7 maintenance mode on"
    "$PHP" artisan down --retry=30 --no-ansi || true
    trap '"$PHP" artisan up --no-ansi || true' EXIT   # always come back up, even on errors

    step "2/7 get the latest code ($BRANCH)"
    BEFORE="$(git rev-parse --short HEAD)"
    git fetch --depth=1 origin "$BRANCH"
    git reset --hard "origin/$BRANCH"        # the server never edits tracked files
    step "    $BEFORE → $(git rev-parse --short HEAD)"

    trap - EXIT
    exec env JD_UPDATE_PHASE=2 bash "$APP_DIR/deploy/hostinger/update.sh" "$@"
fi
trap '"$PHP" artisan up --no-ansi || true' EXIT
chmod -R go+rX public   # files the pull rewrote must stay readable for the web server

step "3/7 composer install (production)"
"$PHP" "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts 2>&1
"$PHP" artisan package:discover --no-ansi

step "4/7 build CSS/JS"
"$PHP" artisan view:clear --no-ansi >/dev/null   # Tailwind scans compiled views: same CSS as a fresh build
PATH="$NODE_DIR:$PATH" npm ci --no-audit --no-fund --loglevel=error
PATH="$NODE_DIR:$PATH" npm run build --silent
rm -rf node_modules

step "5/7 database migrations"
"$PHP" artisan migrate --force --no-ansi

step "6/7 refresh caches"
"$PHP" artisan optimize:clear --no-ansi
"$PHP" artisan optimize --no-ansi
chmod -R u+rwX storage bootstrap/cache
chmod -R go+rX public   # the web server reads static files as "other"

step "7/7 done – maintenance mode off"
}

main "$@"
exit
