#!/usr/bin/env bash

function app_php_extension_install_and_enable() {
    local ext_name="${1}"
    : "${ext_name:?'argument is required'}"

    app_php_extension_install "${ext_name}" \
    && \
    app_php_extension_enable "${ext_name}"
}

function app_php_extension_is_enabled() {
    local ext_name="${1}"
    : "${ext_name:?'argument is required'}"

    php -m | grep --ignore-case "^${ext_name}$"
}

function app_php_extension_install() {
    local ext_name="${1}"
    : "${ext_name:?'argument is required'}"

    if app_php_extension_is_enabled "${ext_name}" ; then
        return 0
    fi

    sudo pecl install "${ext_name}"
}

function app_php_extension_enable() {
    local ext_name="${1}"
    : "${ext_name:?'argument is required'}"

    if app_php_extension_is_enabled "${ext_name}" ; then
        return 0
    fi

    ini_dir="$(php -i | grep --only-matching --perl-regexp '(?<=Scan this dir for additional .ini files => ).+')"
    mkdir -p "${ini_dir}"

    sudo "${SHELL}" -c "echo 'extension=${ext_name}' > '${ini_dir}/${ext_name}.ini'"

    app_php_extension_is_enabled "${ext_name}"
}

function app_arangodb_wait() {
    local timeout="${1:-30}"
    : "${timeout:?'argument is required'}"

    local host="${2:-arangodb}"
    : "${host:?'argument is required'}"

    local port="${3:-8529}"
    : "${port:?'argument is required'}"

    timeout \
        "${timeout}" \
        "${SHELL}" -c \
        -- \
        "while ! curl \
            --fail \
            --silent \
            --show-error \
            --header 'Authorization: Basic $(echo -n 'root:${ARANGO_ROOT_PASSWORD}' | base64)' \
            --header 'Accept: application/json' \
            'http://${host}:${port}/_api/version';
        do
            sleep 1;
            echo 'Waiting for ArangoDB on http://${host}:${port};'
        done;"
}

function app_arangodb_database_create() {
    local name="${1:-${ARANGODB_CACHE_OPTION_DATABASE}}"
    : "${name:?'argument is required'}"

    local host="${2:-arangodb}"
    : "${host:?'argument is required'}"

    local port="${3:-8529}"
    : "${port:?'argument is required'}"

    curl \
        --fail \
        --request POST \
        --header "Authorization: Basic $(echo -n 'root:${ARANGO_ROOT_PASSWORD}' | base64)" \
        --header 'Accept: application/json' \
        --data "{\"name\": \"${name}\"}" \
        "http://${host}:${port}/_api/database"
}
