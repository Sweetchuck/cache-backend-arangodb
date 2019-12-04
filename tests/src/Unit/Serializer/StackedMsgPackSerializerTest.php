<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Unit\Serializer;

use Sweetchuck\CacheBackend\ArangoDb\Serializer\Base64Serializer;
use Sweetchuck\CacheBackend\ArangoDb\Serializer\MsgPackSerializer;
use Sweetchuck\CacheBackend\ArangoDb\Serializer\StackedSerializer;
use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

/**
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\StackedSerializer
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\MsgPackSerializer
 */
class StackedMsgPackSerializerTest extends SerializeTestBase
{

    /**
     * {@inheritdoc}
     */
    protected $requiredExtension = 'msgpack';

    public function casesInputOutputPairs(): array
    {
        return [
            'string' => ['abcd', 'pGFiY2Q='],
            'array' => [['a' => 'b'], 'gaFhoWI='],
        ];
    }

    public function createInstance(array $options = []): SerializerInterface
    {
        return new StackedSerializer(
            new MsgPackSerializer(),
            new Base64Serializer(),
        );
    }
}
