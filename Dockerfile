FROM php:8.3-apache

# Extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libwebp-dev \
    libzip-dev libicu-dev libonig-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo pdo_mysql mysqli \
        gd zip intl mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Activer mod_rewrite Apache
RUN a2enmod rewrite

# Dossier uploads accessible en écriture
RUN mkdir -p /var/www/html/uploads/activites /var/www/html/uploads/avatars \
    && chown -R www-data:www-data /var/www/html/uploads

# Config PHP pour la production
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Config Apache : DocumentRoot = racine du projet
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

EXPOSE 80
