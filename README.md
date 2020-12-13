# Psr/Cache compatible pool implementation with ArangoDB

[![CircleCI](https://circleci.com/gh/Sweetchuck/cache-backend-arangodb.svg?style=svg)](https://circleci.com/gh/Sweetchuck/cache-backend-arangodb)
[![codecov](https://codecov.io/gh/Sweetchuck/cache-backend-arangodb/branch/1.x/graph/badge.svg)](https://codecov.io/gh/Sweetchuck/cache-backend-arangodb)


## Supported interfaces

* [`\Psr\SimpleCache\CacheInterface`](https://github.com/php-fig/simple-cache/blob/master/src/CacheInterface.php)
* [`\Psr\Cache\CacheItemPoolInterface`](https://github.com/php-fig/cache/blob/master/src/CacheItemPoolInterface.php)
* [`\Cache\TagInterop\TaggableCacheItemPoolInterface`](https://github.com/php-cache/tag-interop/blob/master/TaggableCacheItemPoolInterface.php)


## Example

```php
<?php

declare(strict_types = 1);

use Sweetchuck\CacheBackend\ArangoDb\CacheItemPool;
use ArangoDBClient\ConnectionOptions;

require_once __DIR__ . '/vendor/autoload.php';

$pool = new CacheItemPool();
$pool
    ->setConnectionOptions([
        ConnectionOptions::OPTION_ENDPOINT => 'tcp://127.0.0.1:8529',
        ConnectionOptions::OPTION_AUTH_USER => 'me',
        ConnectionOptions::OPTION_AUTH_PASSWD => 'my_password',
        ConnectionOptions::OPTION_DATABASE => 'my_project_01',
    ])
    ->setCollectionName('cache_dummy');

$item_my01 = $pool
    ->getItem('my01')
    ->setTags(['my_tag_01'])
    ->set([
        'foo' => 'bar-' . date('H-i-s'),
    ]);
$pool->save($item_my01);

$item_my02 = $pool
    ->getItem('my01')
    ->setTags(['my_tag_02'])
    ->set([
        'foo' => 'bar-' . date('H-i-s'),
    ]);
$pool->save($item_my02);

$pool->invalidateTags(['my_tag_01']);
```


## Links

* [ArangoDB](https://www.arangodb.com/)
* [PHP-FIG - Caching Interface](https://github.com/php-fig/cache)
* [PHP Cache](http://www.php-cache.com/en/latest/)
* [PHP Cache - Integration tests](https://github.com/php-cache/integration-tests)
