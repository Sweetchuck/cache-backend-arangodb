<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Helper;

use ArangoDBClient\Connection;
use ArangoDBClient\ConnectionOptions;
use ArangoDBClient\UpdatePolicy;
use Sweetchuck\CacheBackend\ArangoDb\CacheItemPool;

/**
 * @method int|string dataName()
 * @method string     name()
 */
trait ConnectionTrait
{
    protected static ?Connection $connection = null;

    /**
     * @var \Sweetchuck\CacheBackend\ArangoDb\CacheItemPool[]
     */
    protected array $cachePools = [];

    protected string $connectionEnvVarNamePrefix = 'ARANGODB_CACHE_OPTION_';

    protected function tearDownConnections(): static
    {
        foreach ($this->cachePools as $pool) {
            $pool->removeBin();
        }

        return $this;
    }

    protected function getCollectionName(): string
    {
        $dataNameSafe = preg_replace(
            '/[^a-z0-9_\-]/i',
            '_',
            (string) $this->dataName(),
        );

        return sprintf(
            'cache_%s_%s_%s',
            date('Ymd_His'),
            $this->name() ?: 'unknown',
            $dataNameSafe ?: 0,
        );
    }

    protected function getConnection(): Connection
    {
        if (!static::$connection) {
            static::$connection = new Connection($this->getConnectionOptions());
        }

        return static::$connection;
    }

    /**
     * @phpstan-return cache-backend-arangodb-schema-collection-options
     */
    protected function getConnectionOptions(): array
    {
        $default = $this->getConnectionOptionsDefault();

        return array_replace_recursive(
            $default,
            $this->getConnectionOptionsEnvVar(array_keys($default)),
        );
    }

    /**
     * @phpstan-return cache-backend-arangodb-schema-collection-options
     */
    protected function getConnectionOptionsDefault(): array
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
            ConnectionOptions::OPTION_CREATE => true,
            ConnectionOptions::OPTION_DATABASE => $this->getCollectionName(),
        ];
    }

    /**
     * @param array<string> $keys
     *
     * @return array<string, string>
     */
    protected function getConnectionOptionsEnvVar(array $keys): array
    {
        $options = [];
        foreach ($keys as $key) {
            $value = getenv($this->connectionEnvVarNamePrefix . mb_strtoupper($key));
            if ($value === false) {
                continue;
            }

            $options[$key] = $value;
        }

        return $options;
    }

    /**
     * @see \Cache\IntegrationTests\CachePoolTest::createCachePool
     */
    public function createCachePool(): CacheItemPool
    {
        $connection = $this->getConnection();
        $pool = new CacheItemPool();
        $pool->setConnection($connection);
        $pool->setCollectionName($this->getCollectionName());

        $this->cachePools[] = $pool;

        return $pool;
    }

    /**
     * @see \Cache\IntegrationTests\SimpleCacheTest::createSimpleCache
     */
    public function createSimpleCache(): CacheItemPool
    {
        return $this->createCachePool();
    }
}
