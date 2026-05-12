ARG PHP_VERSION=8.2

FROM php:${PHP_VERSION}-alpine

# Composer uses git to determine the root package version
RUN apk add --no-cache git

# Install necessary PHP extensions
COPY --from=ghcr.io/mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions dom json xdebug

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer