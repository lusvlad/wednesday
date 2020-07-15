FROM php:7.4-fpm AS base

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    curl \
    libfcgi0ldbl \
    && pecl install redis \
    && docker-php-ext-enable redis

ENV LC_ALL C.UTF-8

ARG UID
ENV UID $UID
RUN addgroup --gid $UID --system app && adduser --uid $UID --system --disabled-login --disabled-password --gid $UID app

COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

ENV TZ=Europe/Moscow

FROM base AS fpm

CMD ["/usr/local/sbin/php-fpm", "--force-stderr", "--fpm-config", "/usr/local/etc/php-fpm.d/www.conf"]
