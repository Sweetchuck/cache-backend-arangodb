<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

use ArangoDBClient\Collection;
use ArangoDBClient\CollectionHandler;
use ArangoDBClient\Connection;
use ArangoDBClient\Cursor;
use ArangoDBClient\DocumentHandler;
use ArangoDBClient\Statement;
use ArangoDBClient\UpdatePolicy;
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
 * @psalm-import-type ArangoDbConnectionOptions from \Sweetchuck\CacheBackend\ArangoDb\PsalmTypes
 * @psalm-import-type ExecuteStatementData      from \Sweetchuck\CacheBackend\ArangoDb\PsalmTypes
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
     * @var array<string, array<string, \Sweetchuck\CacheBackend\ArangoDb\CacheItem>>
     */
    protected static array $deferredItems = [];

    protected string $uri = '';

    // region connectionOptions
    /**
     * Keys are \ArangoDBClient\ConnectionOptions::OPTION_*
     *
     * @phpstan-var CacheBackendArangoDbConnectionOptions
     *
     * @see \ArangoDBClient\ConnectionOptions
     */
    protected array $connectionOptions = [];

    /**
     * @phpstan-return CacheBackendArangoDbConnectionOptions
     */
    public function getConnectionOptions(): array
    {
        return $this->connectionOptions;
    }

    /**
     * @phpstan-param CacheBackendArangoDbConnectionOptions $connectionOptions
     */
    public function setConnectionOptions(array $connectionOptions): static
    {
        $this->connectionOptions = $connectionOptions;
        $this->initUri();

        return $this;
    }

    /**
     * @phpstan-return CacheBackendArangoDbConnectionOptions
     */
    protected function getDefaultConnectionOptions(): array
    {
        return [
            ConnectionOptions::OPTION_ENDPOINT => 'tcp://127.0.0.1:8529',
            ConnectionOptions::OPTION_AUTH_TYPE => 'Basic',
            ConnectionOptions::OPTION_AUTH_USER => 'root',
            ConnectionOptions::OPTION_AUTH_PASSWD => '',
            ConnectionOptions::OPTION_CONNECTION => 'Close',
            ConnectionOptions::OPTION_CONNECT_TIMEOUT => 3,
            ConnectionOptions::OPTION_RECONNECT => true,
            ConnectionOptions::OPTION_UPDATE_POLICY => UpdatePolicy::LAST,
            ConnectionOptions::OPTION_CREATE => false,
            ConnectionOptions::OPTION_DATABASE => $this->getCollectionName() ?: 'cache',
        ];
    }

    /**
     * @phpstan-return CacheBackendArangoDbConnectionOptions
     */
    protected function getFinalConnectionOptions(): array
    {
        return $this->getConnectionOptions() + $this->getDefaultConnectionOptions();
    }
    // endregion

    // region connection
    protected ?Connection $connection = null;

    public function getConnection(): ?Connection
    {
        return $this->connection;
    }

    public function setConnection(?Connection $connection): static
    {
        $this->connection = $connection;
        $this->resetConnection();

        return $this;
    }

    protected function initConnection(): static
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

    protected function resetConnection(): static
    {
        $this->collectionHandler = null;
        $this->collection = null;
        $this->documentHandler = null;

        return $this;
    }
    // endregion

    protected ?Collection $collection;

    protected ?CollectionHandler $collectionHandler;

    protected ?DocumentHandler $documentHandler;

    // region collectionName
    protected string $collectionName = 'cache';

    public function getCollectionName(): string
    {
        return $this->collectionName;
    }

    public function setCollectionName(string $collectionName): static
    {
        if ($collectionName === '') {
            throw new \InvalidArgumentException('ArangoDB collection name can not be empty');
        }

        $this->collectionName = $collectionName;

        return $this;
    }
    // endregion

    protected ValidatorInterface $validator;

    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    public function setValidator(ValidatorInterface $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    protected CacheDocumentConverterInterface $documentConverter;

    public function getDocumentConverter(): CacheDocumentConverterInterface
    {
        return $this->documentConverter;
    }

    public function setDocumentConverter(CacheDocumentConverterInterface $documentConverter): static
    {
        $this->documentConverter = $documentConverter;

        return $this;
    }

    protected SerializerInterface $serializer;

    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    public function setSerializer(SerializerInterface $serializer): static
    {
        $this->serializer = $serializer;

        return $this;
    }

    protected SchemaManagerInterface $schemaManager;

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

    // region Implements - \Psr\SimpleCache\CacheInterface
    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->hasItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->validator->assertKey($key);
        $item = $this->getItem($key);

        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        if ($keys instanceof \Generator) {
            $keys = Utils::fetchAllValuesFromGenerator($keys);
        }

        /** @var array<string> $keys */
        $items = [];
        foreach ($this->getItems($keys) as $item) {
            $items[$item->getKey()] = $item->isHit() ? $item->get() : $default;
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->validator->assertKey($key);

        return $this->setMultiple([$key => $value], $ttl);
    }

    /**
     * {@inheritdoc}
     *
     * @phpstan-param array<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $validator = $this->getValidator();
        $validator
            ->assertValues($values)
            ->assertTtl($ttl);

        foreach ($values as $key => $value) {
            $validator->assertKey($key);
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
    public function delete(string $key): bool
    {
        return $this->deleteItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        if ($keys instanceof \Generator) {
            $keys = Utils::fetchAllValuesFromGenerator($keys);
        }

        return $this->deleteItems($keys);
    }
    // endregion

    // region Implements - \Psr\Cache\CacheItemPoolInterface
    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): CacheItem
    {
        $items = $this->getItems([$key]);

        return reset($items);
    }

    /**
     * {@inheritdoc}
     *
     * @phpstan-return iterable<string, \Sweetchuck\CacheBackend\ArangoDb\CacheItem>
     */
    public function getItems(array $keys = [], bool $allowInvalid = false): iterable
    {
        $this->validator->assertKeys($keys);

        if (!$allowInvalid) {
            $this->garbageCollectionDeferred();
        }
        /** @var array<string, \Sweetchuck\CacheBackend\ArangoDb\CacheItem> $items */
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

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        return $this->deleteItems([$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
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
    public function hasItem(string $key): bool
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

        return $result && $result->getCount() === 1;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
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

        return (bool) $result;
    }

    /**
     * {@inheritdoc}
     *
     * @phpstan-param \Sweetchuck\CacheBackend\ArangoDb\CacheItem $item
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $key = $item->getKey();
        $this->validator->assertKey($key);
        static::$deferredItems[$this->uri][$key] = clone $item;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        foreach (static::$deferredItems[$this->uri] as $item) {
            if (!$this->save($item)) {
                return false;
            }
        }

        return true;
    }
    // endregion

    // region Implements - CacheInterface AND CacheItemPoolInterface
    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        static::$deferredItems[$this->uri] = [];

        $this->initConnection();
        if (!$this->isStorageWritable()) {
            return true;
        }

        return $this->collectionHandler->truncate($this->collection);
    }
    // endregion

    // region Implements - \Cache\TagInterop\TaggableCacheItemPoolInterface
    /**
     * {@inheritdoc}
     */
    public function invalidateTag(string $tag): bool
    {
        return $this->invalidateTags([$tag]);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags): bool
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

    public function garbageCollection(): static
    {
        $this
            ->garbageCollectionDeferred()
            ->garbageCollectionStorage();

        return $this;
    }

    /**
     * @param array<string> $keys
     *
     * @return array<string, \Sweetchuck\CacheBackend\ArangoDb\CacheItem>
     *
     * @throws \ArangoDBClient\Exception
     */
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
            $item = $this->documentConverter->documentToItem($this, $document);
            $item->onFetch();
            $items[$item->getKey()] = $item;
        }

        return $items;
    }

    /**
     * @param array<string> $keys
     *
     * @return array<string, \Sweetchuck\CacheBackend\ArangoDb\CacheItem>
     */
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

    protected function garbageCollectionDeferred(): static
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

    protected function garbageCollectionStorage(): static
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

    public function hasBin(): bool
    {
        return $this->collectionHandler
            && $this->collection
            && $this->collectionHandler->has($this->collection);
    }

    public function removeBin(): static
    {
        if ($this->hasBin()) {
            $this->collectionHandler->drop($this->collection);
        }

        $this->collectionHandler = null;
        $this->documentHandler = null;
        $this->collection = null;

        return $this;
    }

    /**
     * @param int|float|string $key
     */
    public function invalidate(int|float|string $key): static
    {
        return $this->invalidateMultiple([(string) $key]);
    }

    /**
     * @phpstan-param array<int|float|string> $keys
     */
    public function invalidateMultiple(array $keys): static
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

    public function invalidateAll(): static
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

    protected function initUri(): static
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

    /**
     * @param array<string, mixed> $bindVars
     *
     * @throws \ArangoDBClient\Exception
     */
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

    /**
     * @phpstan-return cache-backend-arangodb-execute-statement-data
     * @psalm-return ExecuteStatementData
     */
    protected function getExecuteStatementData(): array
    {
        return [
            'batchSize' => 1000,
            'sanitize' => true,
        ];
    }
}
