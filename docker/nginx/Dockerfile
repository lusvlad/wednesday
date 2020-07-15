FROM nginx:latest AS base

RUN apt-get update && apt-get install -y curl

WORKDIR /var/www/html

ARG UID
ENV UID $UID

RUN addgroup --gid $UID --system app && adduser --uid $UID --system --disabled-login --disabled-password --gid $UID app

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY --chown=app:app public ./public
