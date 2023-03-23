#!/bin/bash

DIR=$(dirname $(readlink -f $0))

export MSYS_NO_PATHCONV=1

TEST=${1:-all}
TAG=${2:-php7.4}
IMAGE="kamermans/composer"

function run_tests()
{
    local GUZZLE_VER=$1
    local DOCKER_TAG=$2

    echo "###############################################"
    echo "# Running tests against Guzzle $GUZZLE_VER"
    echo "###############################################"

    docker run -ti --rm \
        -v "$DIR/../:/test" \
        --workdir=/test/guzzle_environments/$GUZZLE_VER \
        --entrypoint=/bin/sh \
        $IMAGE:$DOCKER_TAG \
        -c 'composer install && vendor/bin/phpunit -vvvv'
}

if [[ $TEST = "all" ]]; then
    run_tests 4 php5.6
    run_tests 5 php7.4
    run_tests 6 php7.4
    run_tests 7 php7.4
    run_tests 7-php8 php8.0
    run_tests 7-php8 php8.1
else
    run_tests $TEST $TAG
fi
