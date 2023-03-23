#!/bin/bash -e

DIR=$(dirname $(readlink -f $0))

ACTION="$1"
IMAGE="kamermans/composer"
COMPOSER_VERSION="2.0.11"
COMPOSER_HASH="d6eee0d4637f4bd82bdae098fceda300dcb3ec35bf502604fbe7510933b8f952"

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
