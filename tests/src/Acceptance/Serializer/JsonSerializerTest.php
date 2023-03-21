<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Acceptance\Serializer;

use Sweetchuck\CacheBackend\ArangoDb\Serializer\JsonSerializer;
use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

/**
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\JsonSerializer
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\BaseSerializer
 */
class JsonSerializerTest extends SerializerTestBase
{

    protected string $requiredExtension = 'json';

    protected function createSerializer(array $options): SerializerInterface
    {
        return (new JsonSerializer())->setOptions($options);
    }

    public static function casesInputOutputPairs(): array
    {
        return [
            'associative array' => [['a' => 'b'], '{"a":"b"}'],
        ];
    }
}
