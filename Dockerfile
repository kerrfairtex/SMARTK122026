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
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/

# Swap entry points: public landing page becomes index.php, RosarioSIS login becomes login.php
RUN mv /var/www/html/index.php /var/www/html/login.php \
    && mv /var/www/html/public/index.php /var/www/html/index.php

# Ensure upload directories exist and permissions are set
RUN mkdir -p assets/FileUploads assets/StudentPhotos assets/UserPhotos public/assets/images \
    && chown -R www-data:www-data /var/www/html \
    && chmod +x /var/www/html/docker-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
