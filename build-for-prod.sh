#!/usr/bin/env bash

set -eux

ENV_FILE=$1
APP_CONFIG_FILE=$2

rm -rf ./.bash_history ./.composer/ ./.dockerignore ./.DS_Store ./.env.local ./.git/ ./.gitignore ./.idea/ \
    ./config/packages/dev ./config/routes/dev ./config/secrets/dev ./config/app_config.* \
    ./var/log/* ./var/cache/* ./vendor/* \
    ./Dockerfile ./docker-compose.yml ./docker/ ./build.sh

cp "${ENV_FILE}" ./.env
composer install --no-dev -a -o
source ./.env
cp "${APP_CONFIG_FILE}" "${CONFIG_FILEPATH}"

rm -rf ./composer.* ./symfony.lock ./.composer/ ./bin ./patches/
rm ./build-for-prod.sh