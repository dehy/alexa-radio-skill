#!/bin/bash

set -eux

export DEBIAN_FRONTEND=noninteractive

export ROOT_PATH=/var/www/
export APP_PATH=${ROOT_PATH}

cd "${APP_PATH}"
export APP_ENV=prod

## Configuring frontend directories
gosu alexa composer -v install -n --no-dev --optimize-autoloader --apcu-autoloader --classmap-authoritative
gosu alexa composer clear-cache

rm -rf .composer .dockerignore .env .env.dist ./docker

rm -rf ${ROOT_PATH}build/*
