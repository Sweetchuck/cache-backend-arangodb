<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Serializer;

use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

abstract class BaseSerializer implements SerializerInterface
{

    /**
     * @var string
     */
    protected $engine = '';

    public function getEngine(): string
    {
        return $this->engine;
    }
}
