#!/usr/bin/env bash

curl \
    --fail \
    --request POST \
    --header "Authorization: Basic $(echo -n 'root:${ARANGO_ROOT_PASSWORD}' | base64)" \
    --header 'Accept: application/json' \
    --data "{\"name\": \"${ARANGODB_CACHE_OPTION_DATABASE}\"}" \
    'http://arangodb:8529/_api/database'
