# PHP image for the api, worker and scheduler containers.
#   dev:  local work. The repo is mounted at /workspace. It has Composer and PCOV.
#   prod: staging and production. The code is in the image. No Composer, no dev packages.
# The prod image is multi-arch (amd64 and arm64, ADR 0017). CI builds it (ADR 0012).

# The PHP extensions that the app needs. The dev and prod images both start from here.
FROM php:8.5-fpm-alpine AS runtime

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql intl zip bcmath pcntl opcache

FROM runtime AS base

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM base AS dev

# PCOV measures test coverage. Only the dev image has it.
RUN install-php-extensions pcov

# Run as a user with the same UID and GID as the developer on the host.
# Then files that the container makes in the bind mount belong to the developer.
ARG UID=1000
ARG GID=1000
RUN addgroup -g "${GID}" dev && adduser -D -u "${UID}" -G dev dev
USER dev

WORKDIR /workspace/backend

# Install the runtime packages only. The package list goes in first, so that Docker can
# use the cached layer when only the app code changes.
FROM base AS build

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist
COPY . .
# The autoloader runs the Laravel package discovery. "artisan --version" proves that the app boots.
RUN composer dump-autoload --no-dev --optimize --no-interaction \
    && php artisan --version

FROM runtime AS prod

# The PHP settings for production: no error output to the user, OPcache without file checks.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf '%s\n' \
        'expose_php = Off' \
        'opcache.enable_cli = 1' \
        'opcache.validate_timestamps = 0' \
        'opcache.memory_consumption = 128' \
        'opcache.max_accelerated_files = 20000' \
        > "$PHP_INI_DIR/conf.d/zz-prod.ini"

WORKDIR /var/www/html
COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

# Not root. php-fpm listens on port 9000. The worker and scheduler use another command.
USER www-data
EXPOSE 9000
CMD ["php-fpm"]
