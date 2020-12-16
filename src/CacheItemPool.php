<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

use ArangoDBClient\CollectionHandler;
use ArangoDBClient\Connection;
use ArangoDBClient\Cursor;
use ArangoDBClient\DocumentHandler;
use ArangoDBClient\Statement;
use ArangoDBClient\UpdatePolicy;
use Cache\Adapter\Common\Exception\InvalidArgumentException as CacheInvalidArgumentException;
use Cache\Adapter\Common\HasExpirationTimestampInterface;
use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use ArangoDBClient\ConnectionOptions;
use Sweetchuck\CacheBackend\ArangoDb\Serializer\NativeSerializer;
use Sweetchuck\CacheBackend\ArangoDb\Validator\BasicValidator;

/**
 * @todo Logger.
 */
class CacheItemPool implements
    CacheItemPoolInterface,
    CacheInterface,
    TaggableCacheItemPoolInterface,
    LoggerAwareInterface
{

    use NowTrait;
    use LoggerAwareTrait;

    /**
     * @var array
     */
    protected static $deferredItems = [];

    /**
     * @var string
     */
    protected $uri = '';

    //region connectionOptions
    /**
     * Keys are \ArangoDBClient\ConnectionOptions::OPTION_*
     *
     * @see \ArangoDBClient\ConnectionOptions
     *
     * @var array
     */
    protected $connectionOptions = [];

    public function getConnectionOptions(): array
    {
        return $this->connectionOptions;
    }

    /**
     * @return $this
     */
    public function setConnectionOptions(array $connectionOptions)
    {
        $this->connectionOptions = $connectionOptions;
        $this->initUri();

        return $this;
    }

    protected function getDefaultConnectionOptions(): array
    {
        return [
            ConnectionOptions::OPTION_ENDPOINT => 'tcp://127.0.0.1:8529',
            ConnectionOptions::OPTION_AUTH_TYPE => 'Basic',
            ConnectionOptions::OPTION_AUTH_USER => 'root',
            ConnectionOptions::OPTION_AUTH_PASSWD => '',
            ConnectionOptions::OPTION_CONNECTION => 'Close',
            ConnectionOptions::OPTION_TIMEOUT => 3,
            ConnectionOptions::OPTION_RECONNECT => true,
            ConnectionOptions::OPTION_UPDATE_POLICY => UpdatePolicy::LAST,
            ConnectionOptions::OPTION_CREATE => false,
            ConnectionOptions::OPTION_DATABASE => $this->getCollectionName() ?: 'cache',
        ];
    }

    protected function getFinalConnectionOptions(): array
    {
        return $this->getConnectionOptions() + $this->getDefaultConnectionOptions();
    }
    //endregion

    //region connection
    /**
     * @var \ArangoDBClient\Connection
     */
    protected $connection;

    public function getConnection(): ?Connection
    {
        return $this->connection;
    }

    public function setConnection(?Connection $connection)
    {
        $this->connection = $connection;
        $this->resetConnection();

        return $this;
    }

    /**
     * @return $this
     */
    protected function initConnection()
    {
        $collectionName = $this->getCollectionName();

        if (!$this->connection) {
            $this->connection = new Connection($this->getFinalConnectionOptions());
        }

        if (!$this->collectionHandler) {
            $this->collectionHandler = new CollectionHandler($this->connection);
            // @todo Currently $this->documentConverter->setDocumentClass() can
            // be changed from outside and the $this->collectionHandler->setDocumentClass()
            // won't be updated.
            $this->collectionHandler->setDocumentClass($this->documentConverter->getDocumentClass());
        }

        if (!$this->collection) {
            try {
                $this->collection = $this->schemaManager->createCollection($this->collectionHandler, $collectionName);
            } catch (\Exception $e) {
                return $this;
            }
        }

        if (!$this->documentHandler) {
            $this->documentHandler = new DocumentHandler($this->connection);
        }

        return $this;
    }

    protected function resetConnection()
    {
        $this->collectionHandler = null;
        $this->collection = null;
        $this->documentHandler = null;

        return $this;
    }
    //endregion

    /**
     * @var \ArangoDBClient\Collection
     */
    protected $collection;

    /**
     * @var \ArangoDBClient\CollectionHandler
     */
    protected $collectionHandler;

    /**
     * @var \ArangoDBClient\DocumentHandler
     */
    protected $documentHandler;

    //region collectionName
    /**
     * @var string
     */
    protected $collectionName = 'cache';

    public function getCollectionName(): string
    {
        return $this->collectionName;
    }

    /**
     * @return $this
     */
    public function setCollectionName(string $collectionName)
    {
        if ($collectionName === '') {
            throw new \InvalidArgumentException('ArangoDB collection name can not be empty');
        }

        $this->collectionName = $collectionName;

        return $this;
    }
    //endregion

    /**
     * @var \Sweetchuck\CacheBackend\ArangoDb\ValidatorInterface
     */
    protected $validator;

    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * @var \Sweetchuck\CacheBackend\ArangoDb\CacheDocumentConverterInterface
     */
    protected $documentConverter;

    public function getDocumentConverter(): CacheDocumentConverterInterface
    {
        return $this->documentConverter;
    }

    public function setDocumentConverter(CacheDocumentConverterInterface $documentConverter)
    {
        $this->documentConverter = $documentConverter;

        return $this;
    }

    /**
     * @var \Sweetchuck\CacheBackend\ArangoDb\SerializerInterface
     */
    protected $serializer;

    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * @var \Sweetchuck\CacheBackend\ArangoDb\SchemaManagerInterface
     */
    protected $schemaManager;

    public function __construct(
        ?ValidatorInterface $validator = null,
        ?CacheDocumentConverterInterface $documentConverter = null,
        ?SerializerInterface $serializer = null,
        ?SchemaManagerInterface $schemaManager = null,
        ?LoggerInterface $logger = null
    ) {
        $this->validator = $validator ?: new BasicValidator();
        $this->documentConverter = $documentConverter ?: new CacheDocumentConverter();
        $this->serializer = $serializer ?: new NativeSerializer();
        $this->schemaManager = $schemaManager ?: new SchemaManager();
        $this->setLogger($logger ?: new NullLogger());
        $this->initUri();
    }

    //region \Psr\SimpleCache\CacheInterface
    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return $this->hasItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        $this->validator->assertKey($key);
        $item = $this->getItem($key);

        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        if ($keys instanceof \Generator) {
            $keys = Utils::fetchAllValuesFromGenerator($keys);
        }

        if (!is_array($keys)) {
            throw new CacheInvalidArgumentException('$keys has to be an array');
        }

        $items = [];
        foreach ($this->getItems($keys) as $item) {
            $items[$item->getKey()] = $item->isHit() ? $item->get() : $default;
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $this->validator->assertKey($key);

        return $this->setMultiple([$key => $value], $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        $this
            ->validator
            ->assertValues($values)
            ->assertTtl($ttl);

        foreach ($values as $key => $value) {
            if (is_int($key)
                || (is_string($key) && preg_match('/^\d+$/', $key))
            ) {
                settype($key, 'string');
            }

            $this->validator->assertKey($key);
            $item = new CacheItem($key);
            $item->set($value);
            $item->expiresAfter($ttl);

            if (!$this->save($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        return $this->deleteItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        if (!is_iterable($keys)) {
            throw new CacheInvalidArgumentException('$keys has to be an iterable. Actual: ' . gettype($keys));
        }

        if ($keys instanceof \Generator) {
            $keys = Utils::fetchAllValuesFromGenerator($keys);
        }

        return $this->deleteItems($keys);
    }
    //endregion

    //region \Psr\Cache\CacheItemPoolInterface
    /**
     * {@inheritdoc}
     *
     * @return \Sweetchuck\CacheBackend\ArangoDb\CacheItem
     */
    public function getItem($key)
    {
        $items = $this->getItems([$key]);

        return reset($items);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Sweetchuck\CacheBackend\ArangoDb\CacheItem[]|\Cache\TagInterop\TaggableCacheItemInterface[]|\Traversable
     */
    public function getItems(array $keys = [], bool $allowInvalid = false)
    {
        $this->validator->assertKeys($keys);

        if (!$allowInvalid) {
            $this->garbageCollectionDeferred();
        }
        $items = array_replace(
            array_fill_keys($keys, null),
            $this->getItemsDeferred($keys),
        );
        $missingKeys = array_keys($items, null, true);
        if (!$missingKeys) {
            return $items;
        }

        $items = array_replace($items, $this->getItemsFromStorage($missingKeys, $allowInvalid));

        foreach (array_keys($items, null, true) as $key) {
            $items[$key] = new CacheItem((string) $key, [], $this->validator);
        }

        return $items;
    }

    protected function getItemsFromStorage(array $keys, bool $allowInvalid): array
    {
        if (!$keys) {
            return [];
        }

        $this->initConnection();
        if (!$this->isStorageReadable()) {
            return [];
        }

        foreach ($keys as &$key) {
            settype($key, 'string');
        }

        $filters = [
            'doc.key IN @keys',
        ];
        $bindVars = [
            '@collection' => $this->getCollectionName(),
            'keys' => $keys,
        ];
        if (!$allowInvalid) {
            $filters[] = '(doc.expires == null || doc.expires > @now)';
            $bindVars['now'] = $this->getNowTimestamp();
        }

        $filters = implode(' && ', $filters);
        $query = <<< AQL
        FOR doc IN @@collection
            FILTER
                $filters
            RETURN doc
        AQL;

        $result = $this->executeStatement($query, $bindVars);
        $items = [];
        foreach ($result as $document) {
            $item = $this->documentConverter->documentToItem($this, $document, $this->serializer);
            $item->onFetch();
            $items[$item->getKey()] = $item;
        }

        return $items;
    }

    protected function getItemsDeferred(array $keys): array
    {
        /** @var \Sweetchuck\CacheBackend\ArangoDb\CacheItem[] $items */
        $items = [];
        foreach ($keys as $key) {
            if (!isset(static::$deferredItems[$this->uri][$key])) {
                continue;
            }

            $items[$key] = clone static::$deferredItems[$this->uri][$key];
            $items[$key]->onFetch();
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key)
    {
        return $this->deleteItems([$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        $this->validator->assertKeys($keys);

        foreach ($keys as $key) {
            unset(static::$deferredItems[$this->uri][$key]);
        }

        $this->initConnection();
        if (!$this->isStorageWritable()) {
            return true;
        }

        $query = <<< AQL
        FOR doc IN @@collection
            FILTER
                doc.key IN @keys
            REMOVE doc IN @@collection
        AQL;
        $this->executeStatement(
            $query,
            [
                '@collection' => $this->getCollectionName(),
                'keys' => $keys,
            ],
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key)
    {
        $this->validator->assertKey($key);

        $this->garbageCollectionDeferred();
        if (isset(static::$deferredItems[$this->uri][$key])) {
            return true;
        }

        $this->initConnection();
        if (!$this->isStorageReadable()) {
            return false;
        }

        // @todo Count query without fetching the document.
        $query = <<< AQL
        FOR doc IN @@collection
            FILTER
                doc.key == @key
                && (
                    doc.expires == null || doc.expires > @now
                )
            RETURN doc
        AQL;
        $result = $this->executeStatement(
            $query,
            [
                '@collection' => $this->getCollectionName(),
                'key' => $key,
                'now' => $this->getNowTimestamp(),
            ],
        );

        return $result ? $result->getCount() === 1 : false;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item)
    {
        /** @var \Sweetchuck\CacheBackend\ArangoDb\CacheItem $item */
        $this->validator->assertKey($item->getKey());

        if ($this->isItemExpired($item)) {
            $this->deleteItem($item->getKey());

            return false;
        }

        $this->initConnection();
        if (!$this->isStorageWritable()) {
            return false;
        }

        $query = <<< AQL
        UPSERT
            @condition
        INSERT
            @insert
        UPDATE
            @update
        IN @@collection
        AQL;

        $result = $this->executeStatement(
            $query,
            ['@collection' => $this->getCollectionName()]
            + $this->documentConverter->itemToUpsertUpdateBindVars($this, $item),
        );

        if ($result) {
            unset(static::$deferredItems[$this->uri][$item->getKey()]);

            if ($item instanceof CacheItem) {
                $item->onSave();
            }
        }

        return $result ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $key = $item->getKey();
        $this->validator->assertKey($key);
        static::$deferredItems[$this->uri][$key] = clone $item;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        foreach (static::$deferredItems[$this->uri] as $item) {
            if (!$this->save($item)) {
                return false;
            }
        }

        return true;
    }
    //endregion

    //region CacheInterface AND CacheItemPoolInterface
    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        static::$deferredItems[$this->uri] = [];

        $this->initConnection();
        if (!$this->isStorageWritable()) {
            return true;
        }

        return $this->collectionHandler->truncate($this->collection);
    }
    //endregion

    //region \Cache\TagInterop\TaggableCacheItemPoolInterface
    /**
     * {@inheritdoc}
     */
    public function invalidateTag($tag)
    {
        return $this->invalidateTags([$tag]);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
        if (!$tags) {
            return true;
        }

        $this->initConnection();
        if (!$this->connection) {
            return true;
        }

        $query = <<< AQL
        FOR doc IN @@collection
            FILTER
                @tags ANY IN doc.tags
            REMOVE doc IN @@collection
        AQL;
        $this->executeStatement(
            $query,
            [
                '@collection' => $this->getCollectionName(),
                'tags' => array_unique($tags),
            ],
        );

        // @todo I think items in the static::$deferredItems[$this->uri] also should be invalidated.
        // @todo Proper return value.
        return true;
    }
    //endregion

    /**
     * @return $this
     */
    public function garbageCollection()
    {
        $this
            ->garbageCollectionDeferred()
            ->garbageCollectionStorage();

        return $this;
    }

    /**
     * @return $this
     */
    protected function garbageCollectionDeferred()
    {
        $now = $this->getNowTimestamp();
        /** @var \Psr\Cache\CacheItemInterface $item */
        foreach (static::$deferredItems[$this->uri] as $item) {
            if (!$item instanceof HasExpirationTimestampInterface) {
                continue;
            }

            $expires = $item->getExpirationTimestamp();
            if ($expires !== null && $expires <= $now) {
                unset(static::$deferredItems[$this->uri][$item->getKey()]);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function garbageCollectionStorage()
    {
        $this->initConnection();
        if (!$this->isStorageReadable()) {
            return $this;
        }

        $query = <<< AQL
        FOR doc IN @@collection
            FILTER
                doc.expires != null
                && doc.expires <= @now
            REMOVE doc IN @@collection
        AQL;

        $this->executeStatement(
            $query,
            [
                '@collection' => $this->getCollectionName(),
                'now' => (int) $this->getNow()->format('U'),
            ],
        );

        return $this;
    }

    public function removeBin()
    {
        if ($this->collectionHandler
            && $this->collection
            && $this->collectionHandler->has($this->collection)
        ) {
            $this->collectionHandler->drop($this->collection);
        }

        $this->collectionHandler = null;
        $this->documentHandler = null;
        $this->collection = null;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function invalidate($key)
    {
        return $this->invalidateMultiple([$key]);
    }

    /**
     * @param string[] $keys
     *
     * @return $this
     */
    public function invalidateMultiple(array $keys)
    {
        if (!$keys) {
            return $this;
        }

        $this->initConnection();
        if (!$this->isStorageWritable()) {
            return $this;
        }

        $query = <<< AQL
        FOR doc IN @@collection
            FILTER
                doc.key IN @keys
            UPDATE
                doc
            WITH
                { expires: @expires }
            IN @@collection
        AQL;

        $this->executeStatement(
            $query,
            [
                '@collection' => $this->getCollectionName(),
                'keys' => $keys,
                'expires' => ((int) $this->getNow()->format('U')) - 1,
            ],
        );

        return $this;
    }

    public function invalidateAll()
    {
        $this->initConnection();
        if (!$this->isStorageWritable()) {
            return $this;
        }

        $query = <<< AQL
        FOR doc IN @@collection
            UPDATE
                doc
            WITH
                { expires: @expires }
            IN @@collection
        AQL;

        $this->executeStatement(
            $query,
            [
                '@collection' => $this->getCollectionName(),
                'expires' => ((int) $this->getNow()->format('U')) - 1,
            ]
        );

        return $this;
    }

    protected function initUri()
    {
        $options = $this->getFinalConnectionOptions();
        $this->uri = sprintf(
            '%s/%s#%s',
            $options[ConnectionOptions::OPTION_ENDPOINT] ?? '',
            $options[ConnectionOptions::OPTION_DATABASE] ?? '',
            $this->getCollectionName(),
        );

        static::$deferredItems += [$this->uri => []];

        return $this;
    }

    protected function isStorageReadable(): bool
    {
        return $this->collection && $this->collectionHandler;
    }

    protected function isStorageWritable(): bool
    {
        return $this->collection || $this->documentHandler;
    }

    protected function isItemExpired(CacheItemInterface $item): bool
    {
        if ($item instanceof HasExpirationTimestampInterface) {
            $expires = $item->getExpirationTimestamp();
            if ($expires !== null && $expires <= $this->getNowTimestamp()) {
                return true;
            }
        }

        return false;
    }

    protected function executeStatement(string $query, array $bindVars): ?Cursor
    {
        $statement = new Statement(
            $this->connection,
            ['query' => $query, 'bindVars' => $bindVars] + $this->getExecuteStatementData(),
        );

        $statement->setDocumentClass($this->documentConverter->getDocumentClass());

        $result = null;
        try {
            $result = $statement->execute();
        } catch (\Exception $e) {
            //
        }

        return $result;
    }

    protected function getExecuteStatementData(): array
    {
        return [
            'batchSize' => 1000,
            'sanitize' => true,
        ];
    }
}
