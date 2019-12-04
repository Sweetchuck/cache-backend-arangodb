<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Acceptance\Serializer;

use ArangoDBClient\Statement;
use PHPUnit\Framework\SkippedTestError;
use PHPUnit\Framework\TestCase;
use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;
use Sweetchuck\CacheBackend\ArangoDb\Tests\Helper\ConnectionTrait;

abstract class SerializerTestBase extends TestCase
{

    use ConnectionTrait;

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->tearDownConnections();
    }

    /**
     * @var string
     */
    protected $requiredExtension = '';

    abstract public function casesInputOutputPairs(): array;

    /**
     * @param mixed $value
     * @param mixed $serialized
     * @param array $options
     *
     * @dataProvider casesInputOutputPairs
     */
    public function testInputOutputPairs($value, $serialized, array $options = []): void
    {
        $this->assertRequiredExtension($this->requiredExtension);

        $pool = $this->createCachePool();
        $pool->setSerializer($this->createSerializer($options));

        $item = $pool->getItem('my_key_01');
        $item->set($value);
        $pool->save($item);

        $query = <<< AQL
        FOR doc IN @@collection
            FILTER
                doc.key == @key
                && doc.value == @value
            RETURN doc
        AQL;

        $connection = $pool->getConnection();
        static::assertNotNull($connection, 'ArangoDB connection initialized');

        $statement = new Statement(
            $connection,
            [
                'query' => $query,
                'batchSize' => 1000,
                'sanitize' => true,
                'bindVars' => [
                    '@collection' => $pool->getCollectionName(),
                    'key' => $item->getKey(),
                    'value' => $serialized,
                ],
            ],
        );
        $result = $statement->execute();
        static::assertSame(1, $result->getCount(), 'document saved as expected');

        $item = $pool->getItem($item->getKey());
        static::assertSame($value, $item->get(), 'cache item value');
    }

    /**
     * @return $this
     */
    protected function assertRequiredExtension(string $extensionName)
    {
        if ($extensionName !== '' && !extension_loaded($extensionName)) {
            $this->markTestSkipped("required extension '{$extensionName}' is not available");
        }

        return $this;
    }

    abstract protected function createSerializer(array $options): SerializerInterface;
}
