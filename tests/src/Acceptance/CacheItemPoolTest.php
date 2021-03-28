<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Acceptance;

use Cache\IntegrationTests\CachePoolTest;
use Sweetchuck\CacheBackend\ArangoDb\Tests\Helper\ConnectionTrait;

/**
 * @covers \Sweetchuck\CacheBackend\ArangoDb\CacheItemPool
 * @covers \Sweetchuck\CacheBackend\ArangoDb\CacheItem
 * @covers \Sweetchuck\CacheBackend\ArangoDb\NowTrait
 * @covers \Sweetchuck\CacheBackend\ArangoDb\CacheDocumentConverter
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Validator\BasicValidator
 */
class CacheItemPoolTest extends CachePoolTest
{
    use ConnectionTrait;

    /**
     * @var array
     *
     * {@inheritdoc}
     */
    protected $skippedTests = [
        'testBinaryData' => 'Not supported',
    ];

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->tearDownService();
    }

    public function tearDownService()
    {
        parent::tearDownService();
        $this->tearDownConnections();
    }
}
