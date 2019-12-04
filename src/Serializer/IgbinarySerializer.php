<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Serializer;

class IgbinarySerializer extends BaseSerializer
{

    /**
     * {@inheritdoc}
     */
    protected $engine = 'igbinary';

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function serialize($value)
    {
        return igbinary_serialize($value);
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    public function unserialize($value)
    {
        return igbinary_unserialize($value);
    }
}
