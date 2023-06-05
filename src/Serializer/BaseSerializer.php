<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Serializer;

use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

abstract class BaseSerializer implements SerializerInterface
{

    protected string $engine = '';

    public function getEngine(): string
    {
        return $this->engine;
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
