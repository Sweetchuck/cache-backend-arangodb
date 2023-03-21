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
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\Base64Serializer
 */
class StackedMsgPackSerializerTest extends SerializeTestBase
{

    protected string $requiredExtension = 'msgpack';

    /**
     * {@inheritdoc}
     */
    public static function casesInputOutputPairs(): array
    {
        return [
            'string' => ['abcd', 'pGFiY2Q='],
            'array' => [['a' => 'b'], 'gaFhoWI='],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createInstance(array $options = []): SerializerInterface
    {
        return new StackedSerializer(
            new MsgPackSerializer(),
            new Base64Serializer(),
        );
    }
}
