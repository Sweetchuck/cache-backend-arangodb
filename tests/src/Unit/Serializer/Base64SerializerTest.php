<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Unit\Serializer;

use Sweetchuck\CacheBackend\ArangoDb\Serializer\Base64Serializer;
use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

/**
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\Base64Serializer
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\BaseSerializer
 */
class Base64SerializerTest extends SerializeTestBase
{

    public static function casesInputOutputPairs(): array
    {
        return [
            'string' => ['abcd', 'YWJjZA=='],
        ];
    }

    public function createInstance(array $options = []): SerializerInterface
    {
        return new Base64Serializer();
    }
}
