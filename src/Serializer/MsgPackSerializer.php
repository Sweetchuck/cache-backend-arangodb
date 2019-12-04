<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Serializer;

class MsgPackSerializer extends BaseSerializer
{

    /**
     * {@inheritdoc}
     */
    protected $engine = 'msgpack';

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function serialize($value)
    {
        return msgpack_serialize($value);
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    public function unserialize($value)
    {
        return msgpack_unserialize($value);
    }
}
