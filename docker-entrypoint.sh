#!/bin/bash
set -e

# Dynamically generate config.inc.php from runtime environment variables
# Supports both standard PostgreSQL and Supabase (which uses port 6543 for pooler)
cat << 'EOF' > /var/www/html/config.inc.php
<?php
$DatabaseServer = getenv('DB_SERVER') ?: (getenv('DATABASE_SERVER') ?: 'localhost');
$DatabaseUsername = getenv('DB_USER') ?: (getenv('DATABASE_USER') ?: 'postgres');
$DatabasePassword = getenv('DB_PASSWORD') ?: (getenv('DATABASE_PASSWORD') ?: '');
$DatabaseName = getenv('DB_NAME') ?: (getenv('DATABASE_NAME') ?: 'postgres');
$DatabasePort = getenv('DB_PORT') ?: (getenv('DATABASE_PORT') ?: '5432');
$DatabaseType = 'postgresql';
$wkhtmltopdfPath = '';
$RosarioLocales = ['en_US.utf8'];
$RosarioNotifyAddress = '';
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

# Dynamic Apache port binding
LISTEN_PORT="${PORT:-10000}"
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
    # Always sync public/index.php -> root index.php so subsequent deploys
    # actually update the served landing page (the guard above blocks the mv).
    if [ -f /var/www/html/public/index.php ]; then
        cp /var/www/html/public/index.php /var/www/html/index.php
        echo "[SWAP] synced public/index.php -> index.php ($(wc -c < /var/www/html/index.php) bytes)"
    fi
fi

# --- Expose landing-page photos at /assets/images (survives the Render disk
# mount at /var/www/html/assets, which would otherwise shadow the image tree) ---
chmod 755 /var/www/html/public /var/www/html/public/assets 2>/dev/null || true
ln -sfn /var/www/html/public/assets/images /var/www/html/assets/images
echo "[SWAP] landing images symlinked to /assets/images."

exec "$@"
