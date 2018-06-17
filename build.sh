#!/bin/bash

set -eux

# https://medium.com/microscaling-systems/labelling-automated-builds-on-docker-hub-f3d073fb8e1#.yldbwesu7

if [ -z "${IMAGE_NAME:-}" ]; then
    echo ! "IMAGE_NAME" variable missing.
    echo eg. IMAGE_NAME="akerbis/alexa-radio-skill" sh $0
    exit 1
fi

function evil_git_dirty {
    [[ $(git diff --shortstat 2> /dev/null | tail -n1) != "" ]] && echo "-dirty"
}

function git_branch {
    git rev-parse --abbrev-ref HEAD
}

function git_commit {
    echo $(git rev-parse --short HEAD)$(evil_git_dirty)
}

VCS_BRANCH="${SOURCE_BRANCH:-$(git_branch)}"
VCS_REF="${SOURCE_COMMIT:-$(git_commit)}"
BUILD_DATE="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"

docker build --build-arg VCS_BRANCH="${VCS_BRANCH}" \
             --build-arg VCS_REF="${VCS_REF}" \
             --build-arg BUILD_DATE="${BUILD_DATE}" \
             -t $IMAGE_NAME .
