FROM php:5.6-alpine

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

ARG WWW_DATA_UID
ARG WWW_DATA_GID

RUN apk --no-cache add shadow

RUN usermod -u ${WWW_DATA_UID} www-data \
    && groupmod -g ${WWW_DATA_GID} www-data

RUN apk add --no-cache ${PHPIZE_DEPS}

RUN curl --insecure -L https://pecl.php.net/get/xdebug-2.5.5.tgz -o /tmp/xdebug.tgz \
    && pecl install --offline /tmp/xdebug.tgz \
    && docker-php-ext-enable xdebug

RUN apk add --no-cache git

RUN docker-php-ext-install pdo pdo_mysql
