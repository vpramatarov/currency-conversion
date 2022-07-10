FROM php:7.4-fpm

COPY ./php.ini /usr/local/etc/php/conf.d/docker-php-config.ini

RUN apt update \
    && apt install -y zlib1g-dev libxslt1-dev libonig-dev libicu-dev libzip-dev g++ git zip unzip openssl git \
    && docker-php-ext-install intl opcache pdo pdo_mysql \
    && pecl install apcu \
    && pecl install redis \
    && docker-php-ext-enable apcu redis \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo pdo_mysql mysqli zip xsl intl opcache exif mbstring

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony/bin/symfony /usr/local/bin/symfony


