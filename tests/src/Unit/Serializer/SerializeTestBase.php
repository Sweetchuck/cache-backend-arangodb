<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Tests\Unit\Serializer;

use PHPUnit\Framework\SkippedTestError;
use PHPUnit\Framework\TestCase;
use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

abstract class SerializeTestBase extends TestCase
{

    /**
     * @var string
     */
    protected $requiredExtension = '';

    abstract public function casesInputOutputPairs(): array;

    /**
     * @param mixed $value
     * @param mixed $serialized
     * @param array $options
     *
     * @dataProvider casesInputOutputPairs
     */
    public function testInputOutputPairs($value, $serialized, array $options = []): void
    {
        $this->assertRequiredExtension($this->requiredExtension);
        $serializer = $this->createInstance($options);
        static::assertSame($serialized, $serializer->serialize($value), 'serialize($value) == $serialized');
        static::assertSame($value, $serializer->unserialize($serialized), 'unserialize($serialized) == $value');
    }

    abstract public function createInstance(array $options = []): SerializerInterface;

    /**
     * @return $this
     */
    protected function assertRequiredExtension(string $extensionName)
    {
        if ($extensionName !== '' && !extension_loaded($extensionName)) {
            $this->markTestSkipped("required extension '{$extensionName}' is not available");
        }

        return $this;
    }
}
