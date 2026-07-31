FROM php:8.3-apache

# System libraries required to build the PHP extensions PipraPay needs
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        libcurl4-openssl-dev \
        libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*

# Core extensions (PDO MySQL, GD, Mbstring, Zip, bcmath, cURL)
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        gd \
        mbstring \
        bcmath \
        zip \
        curl

# Imagick (PECL) - required by the app's requirement check
RUN pecl install imagick \
    && docker-php-ext-enable imagick

# Apache: enable rewrite (the app routes everything through index.php) and use a vhost that allows .htaccess
RUN a2enmod rewrite headers
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# PHP runtime limits: the app allows up to 100MB imports and slow SQL restores
RUN printf 'upload_max_filesize=128M\npost_max_size=130M\nmemory_limit=256M\nmax_execution_time=300\nmax_input_time=300\n' > /usr/local/etc/php/conf.d/zz-piprapay.ini

# Ship the whole app, including bundled gateway vendor dependencies
WORKDIR /var/www/html
COPY . .

# The app writes config files, backups, imports and update packages at runtime
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R u+w /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint-piprapay.sh
COPY docker/piprapay-install.php /usr/local/bin/piprapay-install.php
RUN chmod +x /usr/local/bin/docker-entrypoint-piprapay.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint-piprapay.sh"]
CMD ["apache2-foreground"]
