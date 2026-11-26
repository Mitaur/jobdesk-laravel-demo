#!/bin/bash
# =============================================================================
# JobDesk (Demo) – FIRST deploy on Hostinger shared hosting (Business plan)
# =============================================================================
# Works without SSH: run it once from a one-time hPanel cron job, e.g.
#
#   bash $HOME/domains/<your-domain>/app/deploy/hostinger/setup.sh <db_name> <db_user> '<temporary_db_password>'
#
# Layout (code is OUTSIDE the web root):
#   $HOME/domains/<your-domain>/app          ← git clone of this repo
#   $HOME/domains/<your-domain>/public_html  → symlink to app/public (the only web-visible folder)
#
# Security:
#   - The temporary DB password from the hosting panel is replaced by a new random
#     password that is generated here and written ONLY to app/.env (chmod 600).
#   - Nothing secret is printed: not the passwords, not APP_KEY, not .env.
#   - No log files are written by this script; read its output from the cron job.
# =============================================================================
set -euo pipefail
umask 027

if [ "$#" -ne 3 ]; then
    echo "usage: setup.sh <db_name> <db_user> <temporary_db_password>" >&2
    exit 2
fi

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SITE_DIR="$(dirname "$APP_DIR")"
DOMAIN="$(basename "$SITE_DIR")"
PHP="${PHP_BIN:-/opt/alt/php85/usr/bin/php}"
COMPOSER="${COMPOSER_BIN:-/usr/local/bin/composer}"
NODE_DIR="${NODE_DIR:-/opt/alt/alt-nodejs24/root/usr/bin}"
# Shared hosting limits threads per account: Vite 8 (Rolldown) stops with
# "global thread pool has not been initialized" unless it uses one thread.
export RAYON_NUM_THREADS="${RAYON_NUM_THREADS:-1}" UV_THREADPOOL_SIZE="${UV_THREADPOOL_SIZE:-2}"
MARKER="$APP_DIR/storage/app/.first-deploy-done"

step() { echo "[$(date -u '+%H:%M:%S')] $*"; }

cd "$APP_DIR"

if [ -f "$MARKER" ]; then
    step "Already set up (marker found) – nothing to do. Use update.sh for updates."
    exit 0
fi

step "1/9 PHP: $("$PHP" -r 'echo PHP_VERSION;') · domain: $DOMAIN"

step "2/9 composer install (production, no dev packages)"
# --no-scripts: shared hosting disables proc_open, so composer can't start
# "php artisan ..." itself – we run package:discover ourselves below.
"$PHP" "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts 2>&1
"$PHP" artisan package:discover --no-ansi

step "3/9 build CSS/JS with Node $("$NODE_DIR/node" -v)"
PATH="$NODE_DIR:$PATH" npm ci --no-audit --no-fund --loglevel=error
PATH="$NODE_DIR:$PATH" npm run build --silent
rm -rf node_modules   # not needed at runtime (saves disk + inodes)

step "4/9 .env (server only, chmod 600)"
if [ ! -f .env ]; then
    cp .env.example .env
fi
chmod 600 .env
JD_APP_URL="https://$DOMAIN" JD_DB_NAME="$1" JD_DB_USER="$2" JD_DB_TEMP_PASSWORD="$3" \
    "$PHP" deploy/hostinger/configure-env.php .env

step "5/9 app key"
"$PHP" artisan key:generate --force --no-ansi

step "6/9 database: migrate + demo data"
"$PHP" artisan migrate --force --seed --no-ansi

step "7/9 caches (config, routes, views, events)"
"$PHP" artisan optimize --no-ansi

step "8/9 permissions"
chmod -R u+rwX storage bootstrap/cache
# The web server reads static files (CSS/JS, robots.txt, .htaccess) as "other";
# everything else stays private (umask 027). PHP itself runs as the account user.
chmod -R go+rX public
chmod 600 .env

step "9/9 web root → app/public"
cd "$SITE_DIR"
if [ -L public_html ]; then
    step "public_html is already a symlink"
else
    if [ -e public_html ]; then
        BACKUP="public_html.hosting-default.$(date -u +%Y%m%d%H%M%S)"
        mv public_html "$BACKUP"          # renamed, NOT deleted
        step "renamed the default public_html to $BACKUP"
    fi
    ln -s app/public public_html
fi

touch "$MARKER"
step "Done. $("$PHP" "$APP_DIR/artisan" --version --no-ansi)"
