FROM php:8.2-apache

# Install system packages and PHP extensions required by Moodle.
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libicu-dev \
    libxml2-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libpq-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
      gd \
      intl \
      mysqli \
      pdo \
      pdo_mysql \
      soap \
      zip \
      opcache \
    && rm -rf /var/lib/apt/lists/*

# Enable rewrite module
RUN a2enmod rewrite

# Moodle commonly needs this to allow .htaccess rules.
RUN sed -ri "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf

# Create moodledata directory with proper permissions
RUN mkdir -p /app/moodledata \
    && chown -R www-data:www-data /app/moodledata \
    && chmod -R 0777 /app/moodledata

WORKDIR /var/www/html

# Copy application files - use --chown to set ownership during copy
COPY --chown=www-data:www-data . /var/www/html

# Copy Railway config as the main config.php
COPY --chown=www-data:www-data config.railway.php /var/www/html/config.php

# Verify cache directory was copied
RUN ls -la /var/www/html/cache/classes/ || echo "Cache classes directory missing!"

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
