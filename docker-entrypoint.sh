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

exec "$@"
