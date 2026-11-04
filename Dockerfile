
FROM php:8.2-apache

# Install PHP extensions for MySQL
RUN docker-php-ext-install pdo_mysql mysqli

# Install required packages and ZIP extension
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       git unzip libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application source code
COPY Final_project_note_app/ /var/www/html/

WORKDIR /var/www/html

# Install PHP dependencies
RUN if [ -f composer.json ]; then \
      composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# Create upload directories and grant Apache write permissions
RUN mkdir -p /var/www/html/uploads/attachments \
    /var/www/html/uploads/avatars \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# Production PHP settings: log errors without corrupting JSON responses
RUN printf "display_errors=Off\nlog_errors=On\n" \
    > /usr/local/etc/php/conf.d/production-errors.ini

# Enable Apache rewrite and configure port 8000
RUN a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 8000/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:8000>/' /etc/apache2/sites-available/000-default.conf

EXPOSE 8000

CMD ["apache2-foreground"]

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd \
    && rm -rf /var/lib/apt/lists/*