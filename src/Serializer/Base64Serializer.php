<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Serializer;

/**
 * This is not a real serializer, because the ::serialize() accepts only strings.
 *
 * Use this together with "igbinary" or "msgpack" in a StackedSerializer.
 */
class Base64Serializer extends BaseSerializer
{

    /**
     * {@inheritdoc}
     */
    protected $engine = 'base64';

    /**
     * @param string $value
     *
     * @return string
     */
    public function serialize($value)
    {
        return base64_encode($value);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function unserialize($value)
    {
        return base64_decode($value);
    }
}
