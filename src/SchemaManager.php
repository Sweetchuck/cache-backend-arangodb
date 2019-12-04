<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

use ArangoDBClient\Collection;
use ArangoDBClient\CollectionHandler;

class SchemaManager implements SchemaManagerInterface
{

    // region collectionOptions
    /**
     * @var array
     */
    protected $collectionOptions = [];

    /**
     * @return array
     *
     * @see \ArangoDBClient\CollectionHandler::create()
     */
    public function getCollectionOptions(): array
    {
        return $this->collectionOptions;
    }

    /**
     * @return $this
     *
     * @see \ArangoDBClient\CollectionHandler::create()
     */
    public function setCollectionOptions(array $options)
    {
        $this->collectionOptions = $options;

        return $this;
    }
    // endregion

    //region indexDefinitions
    /**
     * @var array[]
     */
    protected $indexDefinitions = [
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

    public function getIndexDefinitions(): array
    {
        return $this->indexDefinitions;
    }

    /**
     * @return $this
     *
     * @see \ArangoDBClient\CollectionHandler::createIndex()
     *
     * @link https://www.arangodb.com/docs/stable/indexing-index-basics.html
     */
    public function setIndexDefinitions(array $definitions)
    {
        $this->indexDefinitions = $definitions;

        return $this;
    }
    //endregion

    /**
     * @return $this
     *
     * @see \ArangoDBClient\CollectionHandler::create()
     * @see \ArangoDBClient\CollectionHandler::createIndex()
     *
     * @link https://www.arangodb.com/docs/stable/indexing-index-basics.html
     */
    public function setOptions(array $options)
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
     * {@inheritdoc}
     */
    public function createCollection(
        CollectionHandler $collectionHandler,
        string $collectionName
    ): Collection {
        if ($collectionHandler->has($collectionName)) {
            return $collectionHandler->get($collectionName);
        }

        $collectionId = $collectionHandler->create($collectionName, $this->getCollectionOptions());
        $collection = $collectionHandler->get($collectionId);
        $this->createIndexes($collectionHandler, $collection);

        return $collection;
    }

    protected function createIndexes(
        CollectionHandler $collectionHandler,
        Collection $collection
    ) {
        foreach ($this->indexDefinitions as $definition) {
            $this->createIndex($collectionHandler, $collection, $definition);
        }

        return $this;
    }

    protected function createIndex(
        CollectionHandler $collectionHandler,
        Collection $collection,
        array $definition
    ) {
        if (array_key_exists(CollectionHandler::OPTION_EXPIRE_AFTER, $definition)
            && $definition[CollectionHandler::OPTION_EXPIRE_AFTER] === null
        ) {
            return $this;
        }

        $collectionHandler->createIndex($collection, $definition);

        return $this;
    }
}
