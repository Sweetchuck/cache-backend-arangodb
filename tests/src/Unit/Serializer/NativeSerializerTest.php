<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Unit\Serializer;

use Sweetchuck\CacheBackend\ArangoDb\Serializer\NativeSerializer;
use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

/**
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\NativeSerializer
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\BaseSerializer
 */
class NativeSerializerTest extends SerializeTestBase
{

    public static function casesInputOutputPairs(): array
    {
        return [
            'string' => ['abcd', 's:4:"abcd";'],
        ];
    }

    public function createInstance(array $options = []): SerializerInterface
    {
        return new NativeSerializer();
    }
}
