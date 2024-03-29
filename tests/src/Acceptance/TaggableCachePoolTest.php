<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Acceptance;

use Cache\IntegrationTests\TaggableCachePoolTest as TaggableCachePoolTestBase;
use Sweetchuck\CacheBackend\ArangoDb\Tests\Helper\ConnectionTrait;

/**
 * @covers \Sweetchuck\CacheBackend\ArangoDb\CacheItemPool
 * @covers \Sweetchuck\CacheBackend\ArangoDb\CacheItem
 * @covers \Sweetchuck\CacheBackend\ArangoDb\NowTrait
 * @covers \Sweetchuck\CacheBackend\ArangoDb\CacheDocumentConverter
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Validator\BasicValidator
 */
class TaggableCachePoolTest extends TaggableCachePoolTestBase
{

    use ConnectionTrait;

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->tearDownService();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDownService(): void
    {
        parent::tearDownService();
        $this->tearDownConnections();
    }
}
