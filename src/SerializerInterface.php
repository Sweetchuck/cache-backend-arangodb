<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

/**
 * @todo Better design.
 *
 * WARNING: There is no strict input/output data types, because the "Base64",
 * "Json" and "Loafer" serializers are not real serializers.
 */
interface SerializerInterface
{

    public function getEngine(): string;

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function serialize($value);

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function unserialize($value);
}
