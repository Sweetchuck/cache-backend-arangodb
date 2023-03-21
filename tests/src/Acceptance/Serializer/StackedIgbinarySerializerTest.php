<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Acceptance\Serializer;

use Sweetchuck\CacheBackend\ArangoDb\Serializer\Base64Serializer;
use Sweetchuck\CacheBackend\ArangoDb\Serializer\IgbinarySerializer;
use Sweetchuck\CacheBackend\ArangoDb\Serializer\StackedSerializer;
use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

/**
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\IgbinarySerializer
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\StackedSerializer
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\BaseSerializer
 */
class StackedIgbinarySerializerTest extends SerializerTestBase
{

    protected string $requiredExtension = 'igbinary';

    protected function createSerializer(array $options): SerializerInterface
    {
        return new StackedSerializer(
            new IgbinarySerializer(),
            new Base64Serializer(),
        );
    }

    public static function casesInputOutputPairs(): array
    {
        return [
            'string' => ['abcd', 'AAAAAhEEYWJjZA=='],
            'array' => [['a' => 'b'], 'AAAAAhQBEQFhEQFi'],
        ];
    }
}
