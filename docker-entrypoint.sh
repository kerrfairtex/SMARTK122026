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
cat << 'EOFCONFIG' > /var/www/html/config.inc.php
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
EOFCONFIG

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

# Enable mod_expires (needed for ExpiresActive / ExpiresDefault directives)
a2enmod expires 2>/dev/null || true

# Enable mod_rewrite for routing
a2enmod rewrite 2>/dev/null || true

# Create Apache virtual host with proper routing
cat << 'EOFVHOST' > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:${LISTEN_PORT}>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # --- Routing Rules ---
    RewriteEngine On
    
    # / -> public/index.php (landing page)
    RewriteRule ^/?$ public/index.php [L]
    
    # /login.php -> index.php (login processor)
    RewriteRule ^login\.php$ index.php [L]
    
    # /admin_enroll.php -> admin_enroll.php (no change)
    # /enroll_api.php -> enroll_api.php (no change)
    # /enroll_status.php -> enroll_status.php (no change)
    # /public/... -> public/... (static assets)
    
    # Protect internal files
    RewriteRule ^vendor/ - [F,L]
    RewriteRule (^|/)locale/.*\.(po|pot)$ - [F,L]
    RewriteRule ^(?!assets/FileUploads/).*\.(md|sql)$ - [F,L]
    RewriteRule (^|/)\.(?!well-known) - [F,L]
    RewriteRule (^|/)(LICENSE|COPYRIGHT|composer\.lock|composer\.json|package\.json)$ - [F,L]

    # --- Cache lifetimes for static assets ---
    <LocationMatch "\.(jpg|jpeg|png|webp|gif|ico|svg)$">
        Header set Cache-Control "public, max-age=86400"
        ExpiresActive On
        ExpiresDefault "access plus 1 day"
    </LocationMatch>
    <LocationMatch "\.(css|js|woff2?|ttf|eot)$">
        Header set Cache-Control "public, max-age=86400"
        ExpiresActive On
        ExpiresDefault "access plus 1 day"
    </LocationMatch>
    # Service worker itself: never cache
    <LocationMatch "pwabuilder-sw\.js$">
        Header set Cache-Control "no-cache, no-store, must-revalidate"
        Header set Pragma "no-cache"
        Header set Expires "0"
    </LocationMatch>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOFVHOST

# --- Expose landing-page photos at /assets/images (survives the Render disk
# mount at /var/www/html/assets, which would otherwise shadow the image tree) ---
chmod 755 /var/www/html/public /var/www/html/public/assets 2>/dev/null || true
# Guard: if public/assets/images/ is missing (COPY failure, partial build),
# create it from the source tree or skip the symlink to avoid dangling target.
if [ -d /var/www/html/public/assets/images ]; then
    ln -sfn /var/www/html/public/assets/images /var/www/html/assets/images
    echo "[INFO] landing images symlinked to /assets/images."
else
    echo "[WARN] /var/www/html/public/assets/images missing; landing images NOT symlinked."
fi

# --- Expose landing-page CSS/JS bundles at /css and /js ---
echo "[INFO] symlinking landing CSS/JS bundles from public/ -> docroot..."
mkdir -p /var/www/html/css /var/www/html/js
ln -sfn /var/www/html/public/css/components.css /var/www/html/css/components.css
ln -sfn /var/www/html/public/css/base.css       /var/www/html/css/base.css
ln -sfn /var/www/html/public/css/tokens.css     /var/www/html/css/tokens.css
ln -sfn /var/www/html/public/js/main.js         /var/www/html/js/main.js
ln -sfn /var/www/html/public/js/reveal.js       /var/www/html/js/reveal.js
ln -sfn /var/www/html/public/js/enhancements.js /var/www/html/js/enhancements.js
ln -sfn /var/www/html/public/js/stepper.js      /var/www/html/js/stepper.js
echo "[INFO] CSS/JS bundles symlinked."

# --- Inject build version into cache-busting markers ---
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
