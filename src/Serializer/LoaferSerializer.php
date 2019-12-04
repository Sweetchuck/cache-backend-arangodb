<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Serializer;

/**
 * Use this serializer only when you don't need a serializer :-).
 *
 * If the $value doesn't contains any classes, just scalar values and arrays,
 * then some CPU usage and time can be saved by skipping the
 * serialize/unserialize part.
 */
class LoaferSerializer extends BaseSerializer
{

    /**
     * {@inheritdoc}
     */
    protected $engine = 'loafer';

    /**
     * @param null|bool|int|float|array $value
     *   Class free data structure in any depth.
     *
     * @return null|bool|int|float|array
     */
    public function serialize($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($value)
    {
        return $value;
    }
}
