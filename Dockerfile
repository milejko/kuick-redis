# syntax=docker/dockerfile:1.6

ARG PHP_VERSION=8.3

######################
# Test runner target #
######################
FROM milejko/php:${PHP_VERSION}-fpm AS test-runner

ENV XDEBUG_ENABLE=1 \
    XDEBUG_MODE=coverage
