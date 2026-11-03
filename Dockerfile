
FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql mysqli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       git unzip libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY Final_project_note_app/ /var/www/html/

WORKDIR /var/www/html

RUN if [ -f composer.json ]; then \
      composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

RUN a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 8000/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:8000>/' /etc/apache2/sites-available/000-default.conf

EXPOSE 8000

CMD ["apache2-foreground"]
