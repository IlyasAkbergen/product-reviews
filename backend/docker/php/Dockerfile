FROM dunglas/frankenphp:1-php8.5

RUN apt-get update && apt-get install -y --no-install-recommends \
        librabbitmq-dev \
    && install-php-extensions \
        pdo_mysql \
        zip \
        intl \
        mbstring \
        xml \
        amqp \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /var/www/app

WORKDIR /var/www/app

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY Caddyfile /etc/frankenphp/Caddyfile

EXPOSE 80
