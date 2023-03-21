<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Serializer;

class IgbinarySerializer extends BaseSerializer
{

    protected string $engine = 'igbinary';

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function serialize($value)
    {
        if (!$this->isAvailable()) {
            throw new \LogicException();
        }

        return igbinary_serialize($value);
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    public function unserialize($value)
    {
        if (!$this->isAvailable()) {
            throw new \LogicException();
        }

        return igbinary_unserialize($value);
    }

    protected function isAvailable(): bool
    {
        return extension_loaded('igbinary');
    }
}
