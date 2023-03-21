<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

use ArangoDBClient\Document;

interface CacheDocumentConverterInterface
{

    public function getDocumentClass(): string;

    public function setDocumentClass(string $documentClass): static;

    /**
     * @todo Return value should be the neutral \Psr\Cache\CacheItemInterface.
     */
    public function documentToItem(
        CacheItemPool $pool,
        Document $document,
    ): CacheItem;

    /**
     * @phpstan-return cache-backend-arangodb-item-to-upsert-update-bind-vars
     */
    public function itemToUpsertUpdateBindVars(
        CacheItemPool $pool,
        CacheItem $item,
    ): array;
}
