#!/bin/bash

set -eux

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y --no-install-recommends apt-transport-https locales ca-certificates gnupg2

apt-key add /setup/E5267A6C.gpg # PHP (Launchpad PPA for Ondřej Surý)

mv -f /setup/sources.list.d/* /etc/apt/sources.list.d/
mv -f /setup/entrypoint.sh /
mv -f /setup/composer /usr/local/bin/
mv -f /setup/gosu-1.10-amd64 /usr/local/bin/gosu
mv -f /setup/healthcheck.sh /usr/local/bin/healthcheck.sh

apt-get update
apt-get dist-upgrade -y
apt-get install -y --no-install-recommends \
    curl \
    git \
    zip \
    unzip \
    nginx-light \
    php-apcu \
    php7.3-phpdbg \
    php7.3-cli \
    php7.3-curl \
    php7.3-fpm \
    php7.3-xml \
    php7.3-zip \
    php7.3-mbstring \
    supervisor

chmod +x /usr/local/bin/composer
chmod +x /usr/local/bin/gosu
gosu nobody true # Checking gosu
chmod +x /usr/local/bin/healthcheck.sh

useradd -s /bin/bash -d /var/www -u 1000 alexa
chown -R alexa: /var/www

apt-get -y autoremove
apt-get clean

rm -rf /usr/share/man/*