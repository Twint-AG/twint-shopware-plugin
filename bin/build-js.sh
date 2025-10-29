#!/usr/bin/env bash

set -euo pipefail

# Change to Shop directory
cd /var/www/html

# install dependencies
composer install --no-interaction

# Replace APP_ENV=dev with APP_ENV=prod in .env file
sed -i 's/APP_ENV=dev/APP_ENV=prod/g' .env

# Symlink for Plugin
ln -s /builds/twint-ag/twint-shopware-plugin /var/www/html/custom/plugins/TwintPayment

# Install and active TwintPayment
bin/console plugin:refresh
bin/console plugin:install TwintPayment
bin/console plugin:activate TwintPayment

# Run build scripts
./bin/build-js.sh