#!/usr/bin/env bash

timeout \
    30 \
    $SHELL -c \
    -- \
    "while ! curl \
        --fail \
        --silent \
        --show-error \
        --header 'Authorization: Basic $(echo -n 'root:${ARANGO_ROOT_PASSWORD}' | base64)' \
        --header 'Accept: application/json' \
        'http://arangodb:8529/_api/version';
    do
        sleep 1;
        echo Waiting for ArangoDB on http://arangodb:8529;
    done;"
