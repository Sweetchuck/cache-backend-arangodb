<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

use ArangoDBClient\Collection;
use ArangoDBClient\CollectionHandler;
use ArangoDBClient\Exception;

class SchemaManager implements SchemaManagerInterface
{

    // region collectionOptions
    /**
     * @phpstan-var cache-backend-arangodb-schema-collection-options
     */
    protected array $collectionOptions = [];

    /**
     * @phpstan-return cache-backend-arangodb-schema-collection-options
     *
     * @see \ArangoDBClient\CollectionHandler::create()
     */
    public function getCollectionOptions(): array
    {
        return $this->collectionOptions;
    }

    /**
     * @phpstan-param cache-backend-arangodb-schema-collection-options $options
     *
     * @see \ArangoDBClient\CollectionHandler::create()
     */
    public function setCollectionOptions(array $options): static
    {
        $this->collectionOptions = $options;

        return $this;
    }
    // endregion

    //region indexDefinitions
    /**
     * @phpstan-var array<string, cache-backend-arangodb-schema-index-definition>
     */
    protected array $indexDefinitions = [
        'idx_key' => [
            'name' => 'idx_key',
            CollectionHandler::OPTION_TYPE => CollectionHandler::OPTION_HASH_INDEX,
            CollectionHandler::OPTION_FIELDS => ['key'],
            CollectionHandler::OPTION_UNIQUE => true,
        ],
        'idx_tags' => [
            'name' => 'idx_tags',
            CollectionHandler::OPTION_TYPE => CollectionHandler::OPTION_HASH_INDEX,
            CollectionHandler::OPTION_FIELDS => ['tags[*]'],
        ],
        'idx_expires' => [
            'name' => 'idx_expires',
            CollectionHandler::OPTION_TYPE => CollectionHandler::OPTION_TTL_INDEX,
            CollectionHandler::OPTION_FIELDS => ['expires'],
            CollectionHandler::OPTION_EXPIRE_AFTER => 0,
        ],
        'idx_created' => [
            'name' => 'idx_created',
            CollectionHandler::OPTION_TYPE => CollectionHandler::OPTION_TTL_INDEX,
            CollectionHandler::OPTION_FIELDS => ['created'],
            CollectionHandler::OPTION_EXPIRE_AFTER => null,
        ],
    ];

    /**
     * @phpstan-return array<string, cache-backend-arangodb-schema-index-definition>
     */
    public function getIndexDefinitions(): array
    {
        return $this->indexDefinitions;
    }

    /**
     * @phpstan-param array<string, cache-backend-arangodb-schema-index-definition> $definitions
     *
     * @see \ArangoDBClient\CollectionHandler::createIndex()
     *
     * @link https://www.arangodb.com/docs/stable/indexing-index-basics.html
     */
    public function setIndexDefinitions(array $definitions): static
    {
        $this->indexDefinitions = $definitions;

        return $this;
    }
    //endregion

    /**
     * @phpstan-param cache-backend-arangodb-schema-manager-options $options
     *
     * @see \ArangoDBClient\CollectionHandler::create()
     * @see \ArangoDBClient\CollectionHandler::createIndex()
     *
     * @link https://www.arangodb.com/docs/stable/indexing-index-basics.html
     */
    public function setOptions(array $options): static
    {
        if (array_key_exists('collectionOptions', $options)) {
            $this->setCollectionOptions($options['collectionOptions']);
        }

        if (array_key_exists('indexDefinitions', $options)) {
            $this->setIndexDefinitions($options['indexDefinitions']);
        }

        return $this;
    }

    /**
     * @throws \ArangoDBClient\Exception
     */
    public function createCollection(
        CollectionHandler $collectionHandler,
        string $collectionName,
    ): Collection {
        if ($collectionHandler->has($collectionName)) {
            return $collectionHandler->get($collectionName);
        }

        $collectionId = $collectionHandler->create($collectionName, $this->getCollectionOptions());
        $collection = $collectionHandler->get($collectionId);
        $this->createIndexes($collectionHandler, $collection);

        return $collection;
    }

    /**
     * @throws \ArangoDBClient\Exception
     */
    protected function createIndexes(
        CollectionHandler $collectionHandler,
        Collection $collection,
    ): static {
        foreach ($this->getIndexDefinitions() as $definition) {
            $this->createIndex($collectionHandler, $collection, $definition);
        }

        return $this;
    }

    /**
     * @phpstan-param cache-backend-arangodb-schema-index-definition $definition
     *
     * @throws \ArangoDBClient\Exception
     */
    protected function createIndex(
        CollectionHandler $collectionHandler,
        Collection $collection,
        array $definition,
    ): static {
        if (array_key_exists(CollectionHandler::OPTION_EXPIRE_AFTER, $definition)
            && $definition[CollectionHandler::OPTION_EXPIRE_AFTER] === null
        ) {
            return $this;
        }

        try {
            $collectionHandler->createIndex($collection, $definition);
        } catch (\Exception) {
        }

        return $this;
    }
}
