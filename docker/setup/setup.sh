#!/bin/bash

set -eux

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y --no-install-recommends apt-transport-https locales ca-certificates gnupg2

apt-key add /setup/E5267A6C.gpg # PHP (Launchpad PPA for Ondřej Surý)

mv -f /setup/sources.list.d/* /etc/apt/sources.list.d/
mv -f /setup/entrypoint.sh /
mv -f /setup/healthcheck.sh /usr/local/bin/healthcheck.sh

apt-get update
apt-get dist-upgrade -y
apt-get install -y --no-install-recommends \
    curl \
    git \
    zip \
    unzip \
    patch \
    nginx-light \
    gosu \
    php-apcu \
    php8.0-phpdbg \
    php8.0-cli \
    php8.0-curl \
    php8.0-fpm \
    php8.0-xml \
    php8.0-zip \
    php8.0-mbstring \
    supervisor

gosu nobody true # Checking gosu
chmod +x /usr/local/bin/healthcheck.sh

# Composer Install - https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
RESULT=$?
rm composer-setup.php
# End Composer Install

useradd -s /bin/bash -d /var/www -u 1000 alexa
chown -R alexa: /var/www

apt-get -y autoremove
apt-get clean

rm -rf /usr/share/man/*