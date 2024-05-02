#!/usr/bin/env bash

vendor/bin/phpunit --configuration phpunit.xml --log-junit phpunit.junit.xml --colors=never --coverage-clover phpunit.clover.xml --coverage-html phpunit-coverage-html --coverage-text