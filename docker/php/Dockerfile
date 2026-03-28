FROM php:8.1-fpm

COPY php.ini /usr/local/etc/php/

RUN apt update \
    && apt install -y default-mysql-client zlib1g-dev libzip-dev unzip \
    && docker-php-ext-install pdo_mysql zip

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && composer self-update

WORKDIR /var/www

# Ensure php-fpm listens on all IPv4 interfaces for container networking
# (use 0.0.0.0:9000 so nginx can connect via the 'php' service name).
RUN sed -i "s|^listen = .*|listen = 0.0.0.0:9000|" /usr/local/etc/php-fpm.d/www.conf || true