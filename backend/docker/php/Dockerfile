FROM php:8.5-fpm-alpine

RUN apk add --no-cache \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    icu-dev \
    rabbitmq-c-dev \
    autoconf \
    gcc \
    g++ \
    make \
    && docker-php-ext-install \
        pdo_mysql \
        zip \
        intl \
        mbstring \
        xml \
    && pecl install amqp \
    && docker-php-ext-enable amqp \
    && apk del autoconf gcc g++ make

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www/app

WORKDIR /var/www/app

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-amqp

EXPOSE 9000
