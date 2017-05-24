#!/bin/bash

DIR=$(dirname $(readlink -f $0))
TESTS=${1:-all}

function run_tests()
{
    local GUZZLE_VER=$1

    echo "###############################################"
    echo "# Running tests against Guzzle $GUZZLE_VER"
    echo "###############################################"

    docker run -ti --rm \
        -v $DIR/../:/test \
        --workdir=/test/guzzle_environments/$GUZZLE_VER \
        --entrypoint=/bin/sh \
        composer/composer:php5-alpine \
        -c '([ -f vendor/bin/phpunit ] || composer update); vendor/bin/phpunit -vvvv'
}

if [[ $TESTS = "all" ]]; then
    run_tests 4
    run_tests 5
    run_tests 6
else
    run_tests $TESTS
fi
