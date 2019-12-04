<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Acceptance\Serializer;

use Sweetchuck\CacheBackend\ArangoDb\Serializer\Base64Serializer;
use Sweetchuck\CacheBackend\ArangoDb\Serializer\MsgPackSerializer;
use Sweetchuck\CacheBackend\ArangoDb\Serializer\StackedSerializer;
use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

/**
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\MsgPackSerializer
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\StackedSerializer
 */
class StackedMsgPackSerializerTest extends SerializerTestBase
{

    /**
     * {@inheritdoc}
     */
    protected $requiredExtension = 'msgpack';

    protected function createSerializer(array $options): SerializerInterface
    {
        return new StackedSerializer(
            new MsgPackSerializer(),
            new Base64Serializer(),
        );
    }

    public function casesInputOutputPairs(): array
    {
        return [
            'string' => ['abcd', 'pGFiY2Q='],
            'array' => [['a' => 'b'], 'gaFhoWI='],
        ];
    }
}
