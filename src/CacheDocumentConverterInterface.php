<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

use ArangoDBClient\Document;

interface CacheDocumentConverterInterface
{

    public function getDocumentClass(): string;

    /**
     * @return $this
     */
    public function setDocumentClass(string $documentClass);

    /**
     * @todo Return value should be the neutral \Psr\Cache\CacheItemInterface.
     */
    public function documentToItem(
        CacheItemPool $pool,
        Document $document
    ): CacheItem;

    public function itemToUpsertUpdateBindVars(
        CacheItemPool $pool,
        CacheItem $item
    ): array;
}
