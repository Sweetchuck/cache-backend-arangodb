<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Unit\Serializer;

use Sweetchuck\CacheBackend\ArangoDb\Serializer\LoaferSerializer;
use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

/**
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\LoaferSerializer
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\BaseSerializer
 */
class LoaferSerializerTest extends SerializeTestBase
{

    public static function casesInputOutputPairs(): array
    {
        return [
            'string' => ['abcd', 'abcd'],
            'array' => [['a' => 'b'], ['a' => 'b']],
        ];
    }

    public function createInstance(array $options = []): SerializerInterface
    {
        return new LoaferSerializer();
    }
}
