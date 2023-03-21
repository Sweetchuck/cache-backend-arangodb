<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Unit\Serializer;

use PHPUnit\Framework\TestCase;
use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

abstract class SerializeTestBase extends TestCase
{

    protected string $requiredExtension = '';

    /**
     * @return array<string, mixed>
     */
    abstract public static function casesInputOutputPairs(): array;

    /**
     * @param mixed $value
     * @param mixed $serialized
     * @param array<string, mixed> $options
     *
     * @dataProvider casesInputOutputPairs
     */
    public function testInputOutputPairs(mixed $value, mixed $serialized, array $options = []): void
    {
        $this->assertRequiredExtension($this->requiredExtension);
        $serializer = $this->createInstance($options);
        static::assertSame($serialized, $serializer->serialize($value), 'serialize($value) == $serialized');
        static::assertSame($value, $serializer->unserialize($serialized), 'unserialize($serialized) == $value');
    }

    /**
     * @param array<string, mixed> $options
     */
    abstract public function createInstance(array $options = []): SerializerInterface;

    protected static function assertRequiredExtension(string $extensionName): void
    {
        if ($extensionName !== '' && !extension_loaded($extensionName)) {
            static::markTestSkipped("required extension '{$extensionName}' is not available");
        }
    }
}
