ARG PHP_VERSION=8.1
FROM ghcr.io/roadrunner-server/roadrunner:2.10.1 AS roadrunner
FROM php:${PHP_VERSION}-alpine

WORKDIR /srv/app
RUN addgroup -g "1000" -S php \
  && adduser --system --gecos "" --ingroup "php" --uid "1000" php \
  && mkdir /var/run/rr \
  && chown php /var/run/rr

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
  && install-php-extensions \
    @composer \
    sockets \
    zip

COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr

RUN apk add --no-cache \
    bash \
    git

USER php
