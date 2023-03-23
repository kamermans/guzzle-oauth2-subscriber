#!/bin/bash

DIR=$(dirname $(readlink -f $0))

TESTS=${1:-all}
#IMAGE="phplegacy/php:5.4-composer" #PHP 5.4.45
# not found a working php 5.5 image
#IMAGE="prooph/composer:5.6"
#IMAGE="prooph/composer:7.0"
#IMAGE="prooph/composer:7.1"
#IMAGE="prooph/composer:7.2"
#IMAGE="prooph/composer:7.3"
#IMAGE="prooph/composer:7.4"
#IMAGE="prooph/composer:8.0"
IMAGE="composer:latest" #PHP latest version

function run_tests()
{
    local GUZZLE_VER=$1

    echo "###############################################"
    echo "# Running tests against Guzzle $GUZZLE_VER"
    echo "###############################################"

    docker run --rm \
        -v $DIR/../:/test \
        --workdir=/test/guzzle_environments/$GUZZLE_VER \
        --entrypoint=/bin/sh \
        $IMAGE \
        -c '([ -f vendor/bin/phpunit ] || composer update); vendor/bin/phpunit -vvvv'
}

if [[ $TESTS = "all" ]]; then
    run_tests 4
    run_tests 5
    run_tests 6
    run_tests 7
else
    run_tests $TESTS
fi
