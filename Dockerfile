FROM php:8.1-apache

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

# Fix MPM configuration - remove all MPM module symlinks and enable only one
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# Moodle commonly needs this to allow .htaccess rules.
RUN sed -ri "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . /var/www/html

# Persistent storage path for Moodle dataroot.
RUN mkdir -p /app/moodledata \
    && chown -R www-data:www-data /var/www/html /app/moodledata

EXPOSE 80

CMD ["apache2-foreground"]
