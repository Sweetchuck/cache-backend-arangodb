<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Unit\Serializer;

use Sweetchuck\CacheBackend\ArangoDb\Serializer\JsonSerializer;
use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

/**
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\JsonSerializer
 * @covers \Sweetchuck\CacheBackend\ArangoDb\Serializer\BaseSerializer
 */
class JsonSerializerTest extends SerializeTestBase
{

    protected string $requiredExtension = 'json';

    public static function casesInputOutputPairs(): array
    {
        return [
            'string' => ['abcd', '"abcd"'],
            'array' => [['a', 'b'], '["a","b"]'],
        ];
    }

    public function createInstance(array $options = []): SerializerInterface
    {
        return (new JsonSerializer())->setOptions($options);
    }
}
