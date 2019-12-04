<?php

/**
 * @file
 * XDebug related configuration.
 */

declare(strict_types = 1);

if (extension_loaded('xdebug')) {
    $projectRoot = dirname(__DIR__);

    xdebug_set_filter(
        \XDEBUG_FILTER_CODE_COVERAGE,
        \XDEBUG_PATH_WHITELIST,
        [
            "$projectRoot/src",
        ],
    );
}
