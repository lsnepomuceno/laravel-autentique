# Development / CI image for laravel-autentique.
#
# The PHP version is parameterized so any cell of the CI matrix can be
# reproduced locally. The development host does not need the target version
# installed.
ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-cli-alpine

# zip: Composer extracts dist archives with it
# pcov: coverage driver
#
# Nothing else. The package sends HTTP requests through Laravel's client and
# every one of them is faked in the suite, so no extension beyond what the
# base image carries is needed.
RUN apk add --no-cache git unzip libzip-dev $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" zip \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

# Static analysis needs far more than the 128M CLI default.
RUN printf 'memory_limit = 1G\n' > /usr/local/etc/php/conf.d/zz-dev.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1

# The bind-mounted checkout belongs to the host user, so git inside the
# container refuses to read it and Composer loses the package version.
RUN git config --global --add safe.directory /app

WORKDIR /app
