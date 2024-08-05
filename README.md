# TWINT plugin for Shopware 6 (from 6.5)

# Development environment

Go to infra/demo65 or infra/demo66 and run `docker compose up -d`

Access the container with the following command:

```
docker exec -it sw66 bash
```

Use `sw65` if you are using the demo65 environment.

Install dependencies for the plugin:
```
cd /var/www/plugin
composer install
```

Install shopware dependencies:
```
cd /var/www/html
composer install
```

## Running phpstan
From inside the container, run the following command:

```
./bin/phpstan.sh
```

### Checking coding standards
From inside the container, run the following command:
```
vendor/bin/ecs
```

Fixing coding standards violations:
```
vendor/bin/ecs --fix
```
## Running unit tests
#### Prepare test database
For the first time running unit test, we need create test database:
Assume that we will use `shopware_test` database for unit tests, and you can use other database names as well.
Update `.env.local` to use 
```
DATABASE_URL=mysql://root:root@127.0.0.1:3306/shopware_test
```
then run 
```
bin/console system:install --basic-setup
```
Shopware will run migration scripts and create database tables for test database.

#### Prepare environment file
Unit test will use environment variable in `infra/demo66/.env.test` for test (similarly for `infra/demo65/.env.test`).

Review the `DATABASE_URL` in there and make sure that matches with test database you prepared in the previous step.


#### Run tests
From inside the container, run the following command once:
```
ln -s /var/www/plugin /var/www/html/custom/plugins/TwintPayment
```

Then run unit tests:
```
cd /var/www/html/custom/plugins/TwintPayment
./bin/phpunit.sh
```

## Release management
Tag a new release, let's say version 1.2.3:
```
bin/release.sh 1.2.3
```
CI will then sync with the public GitHub repository.
