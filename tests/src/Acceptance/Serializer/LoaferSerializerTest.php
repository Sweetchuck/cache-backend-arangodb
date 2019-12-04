<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Acceptance\Serializer;

use Sweetchuck\CacheBackend\ArangoDb\Serializer\LoaferSerializer;
use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

/**
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\LoaferSerializer
 */
class LoaferSerializerTest extends SerializerTestBase
{

    protected function createSerializer(array $options): SerializerInterface
    {
        return new LoaferSerializer();
    }

    public function casesInputOutputPairs(): array
    {
        return [
            'associative array' => [['a' => 'b'], ['a' => 'b']],
        ];
    }
}
