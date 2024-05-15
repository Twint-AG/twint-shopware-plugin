#!/usr/bin/env bash

vendor/bin/phpstan analyse -c phpstan.neon --autoload-file=/var/www/html/vendor/autoload.php tests src
