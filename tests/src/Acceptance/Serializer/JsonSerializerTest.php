<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Acceptance\Serializer;

use Sweetchuck\CacheBackend\ArangoDb\Serializer\JsonSerializer;
use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

/**
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\JsonSerializer
 */
class JsonSerializerTest extends SerializerTestBase
{

    /**
     * {@inheritdoc}
     */
    protected $requiredExtension = 'json';

    protected function createSerializer(array $options): SerializerInterface
    {
        return (new JsonSerializer())->setOptions($options);
    }

    public function casesInputOutputPairs(): array
    {
        return [
            'associative array' => [['a' => 'b'], '{"a":"b"}'],
        ];
    }
}
