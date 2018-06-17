#!/bin/bash

set -eux


echo "Running with arguments: " $@

export DEBIAN_FRONTEND=noninteractive

## Purging /tmp
# echo "Purging /tmp"
# rm -rf /tmp/*

export ROOT_PATH=/var/www/
export APP_PATH=${ROOT_PATH}

cd "${APP_PATH}"

if [ "${1:-}" == "debug" ]; then
    tail -f /dev/null
    exit 0
fi
export COVERAGE_ENABLED=false
if [ "${2:-}" == "coverage" ]; then
    COVERAGE_ENABLED=true
fi

php() {
    /usr/local/bin/gosu alexa /usr/bin/php -c /etc/php/7.2/fpm/php.ini "$@"
}
export -f php

if [ "${APP_ENV:-}" == "dev" ]; then
    gosu alexa composer install

    NO_DEBUG="-n --env=dev"
else
    NO_DEBUG="-n --env=prod --no-debug"
fi

echo "+ Clearing cache..."
rm -rf var/cache/*
php "${APP_PATH}bin/console" cache:warmup $NO_DEBUG

echo "+ Launching services..."
supervisord -c /etc/supervisor/supervisord.conf
