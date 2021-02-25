#!/bin/bash -e

DIR=$(dirname $(readlink -f $0))

ACTION="$1"
IMAGE="kamermans/composer"
COMPOSER_VERSION="2.0.11"
COMPOSER_HASH="eabf2917072096a94679193762501319e621e2b369a4a1256b2c27f4e6984477"

cd $DIR
for DOCKERFILE in Dockerfile-*; do
    TAG=$(cut -d- -f2 <<<"$DOCKERFILE")
    echo "Building $DOCKERFILE => $IMAGE:$TAG"
    docker build \
        --pull \
        -f "$DOCKERFILE" \
        -t "$IMAGE:$TAG" \
        --build-arg "COMPOSER_VERSION=$COMPOSER_VERSION" \
        --build-arg "COMPOSER_HASH=$COMPOSER_HASH" \
        .

    if [[ $ACTION = "push" ]]; then
        docker push "$IMAGE:$TAG"
    fi
done
