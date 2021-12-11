#!/bin/bash

DIR=$(dirname $(readlink -f $0))

TESTS=${1:-all}
#IMAGE="composer:1.9.1" #PHP 7.4.2
#IMAGE="pathmotion/composer-php-5.6:latest" #PHP 5.6.33
IMAGE="composer:latest" #PHP Latest Version

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
