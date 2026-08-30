FROM php:8.2-apache

# Install required system packages and dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    zlib1g-dev \
    gettext \
    libcurl4-openssl-dev \
    ca-certificates \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pgsql \
        pdo_pgsql \
        gd \
        zip \
        intl \
        mbstring \
        gettext \
        opcache \
        xml \
        curl \
    && a2enmod rewrite headers expires \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/

# NOTE: index.php<->login.php swap and landing-image symlink are performed at container
# start inside docker-entrypoint.sh (idempotent) so they survive Render build caching
# and redeploys. Do NOT run the mv here — build-time mv is what silently failed live.

# Ensure upload directories exist and permissions are set
RUN mkdir -p assets/FileUploads assets/StudentPhotos assets/UserPhotos public/assets/images \
    && chown -R www-data:www-data /var/www/html \
    && chmod +x /var/www/html/docker-entrypoint.sh

# Expose default Render port
EXPOSE 10000

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
