Twint shopware

#Access to container and install the dependencies
docker exec -it {containerID} bash
```
cd /var/www/plugin
composer install
```
##unit tests
Right now have no way to test the plugin in the vendor/twint-ag/twint-shopware-plugin so I have to create symlink
sudo ln -s /var/www/plugin /var/www/html/custom/plugins/TwintPayment
./bin/phpunit.sh
##phpstan
./bin/phpstan.sh

### Coding standards
Check coding standards with ecs
```
vendor/bin/ecs
```
Fixing coding standards
```
vendor/bin/ecs --fix
```
