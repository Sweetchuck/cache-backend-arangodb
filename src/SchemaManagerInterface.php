<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

use ArangoDBClient\Collection;
use ArangoDBClient\CollectionHandler;

interface SchemaManagerInterface
{

    /**
     * @return $this
     */
    public function createCollection(
        CollectionHandler $collectionHandler,
        string $collectionName
    ): Collection;
}
