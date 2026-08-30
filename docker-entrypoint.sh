#!/bin/bash
set -e

echo "=== Starting SMARTCAMPUS / RosarioSIS Container ==="

# Validate database environment variables
DB_SERVER="${DB_SERVER:-${DATABASE_SERVER:-db.ebyepweqwihdvjecrufk.supabase.co}}"
DB_USER="${DB_USER:-${DATABASE_USER:-postgres}}"
DB_PASSWORD="${DB_PASSWORD:-${DATABASE_PASSWORD:-}}"
DB_NAME="${DB_NAME:-${DATABASE_NAME:-postgres}}"
DB_PORT="${DB_PORT:-${DATABASE_PORT:-5432}}"
SUPABASE_SSL_MODE="${SUPABASE_SSL_MODE:-require}"
DEFAULT_SYEAR="${DEFAULT_SYEAR:-2026}"

if [ -z "$DB_PASSWORD" ]; then
    echo "[WARNING] DB_PASSWORD is not set. Database connection may fail."
fi

# Dynamically generate config.inc.php from runtime environment variables
echo "[INFO] Generating config.inc.php..."
cat << 'EOF' > /var/www/html/config.inc.php
<?php
$DatabaseServer = getenv('DB_SERVER') ?: (getenv('DATABASE_SERVER') ?: 'db.ebyepweqwihdvjecrufk.supabase.co');
$DatabaseUsername = getenv('DB_USER') ?: (getenv('DATABASE_USER') ?: 'postgres');
$DatabasePassword = getenv('DB_PASSWORD') ?: (getenv('DATABASE_PASSWORD') ?: '');
$DatabaseName = getenv('DB_NAME') ?: (getenv('DATABASE_NAME') ?: 'postgres');
$DatabasePort = getenv('DB_PORT') ?: (getenv('DATABASE_PORT') ?: '5432');
$DatabaseType = 'postgresql';
$wkhtmltopdfPath = '';
$RosarioLocales = ['en_US.utf8'];
$RosarioNotifyAddress = '';
$RosarioErrorsAddress = '';
$DefaultSyear = getenv('DEFAULT_SYEAR') ?: '2026';
$Theme = getenv('THEME') ?: 'FlatSIS';
$SupabaseSSLMode = getenv('SUPABASE_SSL_MODE') ?: 'require';
EOF

chown www-data:www-data /var/www/html/config.inc.php
chmod 640 /var/www/html/config.inc.php

# Writable directories
mkdir -p /var/www/html/assets/FileUploads /var/www/html/assets/StudentPhotos /var/www/html/assets/UserPhotos /var/www/html/public/assets/images
chown -R www-data:www-data /var/www/html/assets/FileUploads /var/www/html/assets/StudentPhotos /var/www/html/assets/UserPhotos /var/www/html/public/assets/images
chmod -R 775 /var/www/html/assets/FileUploads /var/www/html/assets/StudentPhotos /var/www/html/assets/UserPhotos /var/www/html/public/assets/images

# Configure Apache port binding dynamically based on $PORT (Render provides PORT, default 10000)
LISTEN_PORT="${PORT:-10000}"
echo "[INFO] Configuring Apache to listen on port ${LISTEN_PORT}..."
echo "Listen ${LISTEN_PORT}" > /etc/apache2/ports.conf

cat << EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:${LISTEN_PORT}>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# --- Swap landing page in as the docroot (idempotent, at container start) ---
# Render build-time `mv` silently failed on the deployed instance, so the login
# screen kept serving as `/`. This runs every start but only once thanks to the guard.
echo "[SWAP] checking entry-point swap (index.php <-> login.php)..."
if [ ! -f /var/www/html/login.php ]; then
    if [ -f /var/www/html/index.php ] && [ -f /var/www/html/public/index.php ]; then
        mv /var/www/html/index.php /var/www/html/login.php
        mv /var/www/html/public/index.php /var/www/html/index.php
        echo "[SWAP] done: / is now the landing page, /login.php is the RosarioSIS login."
    else
        echo "[SWAP] SKIPPED: source files missing (index.php=$([ -f /var/www/html/index.php ] && echo yes || echo no), public/index.php=$([ -f /var/www/html/public/index.php ] && echo yes || echo no))"
    fi
else
    echo "[SWAP] already applied (login.php exists)."
    if [ -f /var/www/html/public/index.php ]; then
        cp /var/www/html/public/index.php /var/www/html/index.php
        echo "[SWAP] synced public/index.php -> index.php ($(wc -c < /var/www/html/index.php) bytes)"
    fi
fi

# --- Expose landing-page photos at /assets/images (survives the Render disk
# mount at /var/www/html/assets, which would otherwise shadow the image tree) ---
chmod 755 /var/www/html/public /var/www/html/public/assets 2>/dev/null || true
# Guard: if public/assets/images/ is missing (COPY failure, partial build),
# create it from the source tree or skip the symlink to avoid dangling target.
if [ -d /var/www/html/public/assets/images ]; then
    ln -sfn /var/www/html/public/assets/images /var/www/html/assets/images
    echo "[SWAP] landing images symlinked to /assets/images."
else
    echo "[SWAP] WARN: /var/www/html/public/assets/images missing; landing images NOT symlinked."
fi

# --- Expose landing-page CSS/JS bundles at /css and /js (the landing page
# references /css/components.css and /js/{main,reveal,enhancements,stepper}.js
# at the docroot; without these symlinks every CSS/JS asset 404s and the page
# renders unstyled with no JS. Mirror the image-symlink pattern above.
echo "[SWAP] symlinking landing CSS/JS bundles from public/ -> docroot..."
mkdir -p /var/www/html/css /var/www/html/js
ln -sfn /var/www/html/public/css/components.css /var/www/html/css/components.css
ln -sfn /var/www/html/public/css/base.css       /var/www/html/css/base.css
ln -sfn /var/www/html/public/css/tokens.css     /var/www/html/css/tokens.css
ln -sfn /var/www/html/public/js/main.js         /var/www/html/js/main.js
ln -sfn /var/www/html/public/js/reveal.js       /var/www/html/js/reveal.js
ln -sfn /var/www/html/public/js/enhancements.js /var/www/html/js/enhancements.js
ln -sfn /var/www/html/public/js/stepper.js      /var/www/html/js/stepper.js
echo "[SWAP] CSS/JS bundles symlinked."

# --- Inject build version into cache-busting markers ---
# Render exposes $RENDER_GIT_COMMIT (full SHA) at runtime. Use the short SHA
# so every deploy automatically gets a unique CACHE_NAME and a matching
# <!-- build: ... --> marker in the landing HTML. This way PWA users get
# the new asset bundle on next visit (activate handler purges old cache)
# and the rendered HTML source proves which commit is live.
#
# Note: the SWAP above mv's public/index.php → index.php on first start, then
# cp's it on subsequent restarts. We edit BOTH paths so the build marker is
# updated regardless of which one the SWAP left in place. Editing the source
# also ensures the next cold-start SWAP picks up our marker.
BUILD_SHA="${RENDER_GIT_COMMIT:-local-dev}"
BUILD_SHORT="$(printf '%s' "$BUILD_SHA" | cut -c1-8)"
CACHE_NAME="smartcamp-k12-${BUILD_SHORT}"
BUILD_TS="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
echo "[BUILD] version=${BUILD_SHORT} cache=${CACHE_NAME} ts=${BUILD_TS}"

for sw in /var/www/html/pwabuilder-sw.js /var/www/html/phone/download/pwabuilder-sw.js; do
    if [ -f "$sw" ]; then
        sed -i "s|const CACHE_NAME = 'smartcamp-k12-[a-zA-Z0-9-]*';|const CACHE_NAME = '${CACHE_NAME}';|" "$sw"
        echo "[BUILD] injected CACHE_NAME into ${sw#/var/www/html/}"
    else
        echo "[BUILD] WARN: ${sw#/var/www/html/} not found, skipping"
    fi
done

# Edit the HTML build marker in BOTH possible locations.
for html in /var/www/html/index.php /var/www/html/public/index.php; do
    if [ -f "$html" ]; then
        sed -i "s|<!-- build: [a-zA-Z0-9]* [0-9TZ:.-]* -->|<!-- build: ${BUILD_SHORT} ${BUILD_TS} -->|" "$html"
        echo "[BUILD] injected build marker into ${html#/var/www/html/}"
    fi
done

echo "[INFO] Entrypoint initialization complete. Starting Apache..."
exec "$@"
