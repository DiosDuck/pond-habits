# PHP image for the api, worker and scheduler containers.
# The "dev" target is for local work. The production target comes in Phase 4.
FROM php:8.5-fpm-alpine AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql intl zip bcmath pcntl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM base AS dev

# Run as a user with the same UID and GID as the developer on the host.
# Then files that the container makes in the bind mount belong to the developer.
ARG UID=1000
ARG GID=1000
RUN addgroup -g "${GID}" dev && adduser -D -u "${UID}" -G dev dev
USER dev

WORKDIR /workspace/backend
