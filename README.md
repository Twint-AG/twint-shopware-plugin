Twint shopware

#Access to container and install the dependencies
docker exec -it {containerID} bash
cd /var/www/plugin
composer install
##unit tests
Right now have no way to test the plugin in the vendor/twint/twint so I have to create symlink
sudo ln -s /var/www/plugin /var/www/html/custom/plugins/TwintPayment
./bin/phpunit.sh
##phpstan
./bin/phpstan.sh
