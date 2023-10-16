FROM math3usmartins/php-minibus-builder:latest
ARG DEBIAN_FRONTEND=noninteractive

RUN apt update \
    && apt install -y \
        php5.6-pdo \
        php5.6-mysql

ENV COMPOSER_HOME="/var/www/.composer"
ENV PATH="$PATH:$COMPOSER_HOME/vendor/bin"
