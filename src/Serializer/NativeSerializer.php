<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Serializer;

class NativeSerializer extends BaseSerializer
{

    /**
     * {@inheritdoc}
     */
    protected $engine = 'native';

    public function setOptions(array $options)
    {
        if (array_key_exists('unserializeOptions', $options)) {
            $this->setUnserializeOptions($options['unserializeOptions']);
        }

        return $this;
    }

    // region unserializeOptions
    /**
     * @var array
     */
    protected $unserializeOptions = [];

    /**
     * @return array
     */
    public function getUnserializeOptions()
    {
        return $this->unserializeOptions;
    }

    /**
     * @param array $options
     *
     * @return $this
     *
     * @see \unserialize()
     */
    public function setUnserializeOptions($options)
    {
        $this->unserializeOptions = $options;

        return $this;
    }
    // endregion

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function serialize($value)
    {
        return serialize($value);
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    public function unserialize($value)
    {
        return unserialize($value, $this->getUnserializeOptions());
    }
}
