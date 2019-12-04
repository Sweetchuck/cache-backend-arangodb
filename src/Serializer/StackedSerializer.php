<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Serializer;

use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

class StackedSerializer extends BaseSerializer
{

    /**
     * @var \Sweetchuck\CacheBackend\ArangoDb\SerializerInterface[]
     */
    protected $serializers = [];

    /**
     * @return \Sweetchuck\CacheBackend\ArangoDb\SerializerInterface[]
     */
    public function getSerializers(): array
    {
        return $this->serializers;
    }

    /**
     * @param \Sweetchuck\CacheBackend\ArangoDb\SerializerInterface[] $serializers
     *
     * @return $this
     */
    public function setSerializers(array $serializers)
    {
        $this->serializers = array_values($serializers);
        $this->updateEngine();

        return $this;
    }

    public function addSerializer(SerializerInterface $serializer)
    {
        $this->serializers[] = $serializer;
        $this->updateEngine();

        return $this;
    }

    public function __construct(SerializerInterface ...$serializers)
    {
        $this->setSerializers($serializers);
    }

    /**
     * @return $this
     */
    protected function updateEngine()
    {
        $engines = [];
        foreach ($this->getSerializers() as $serializer) {
            $engines[] = $serializer->getEngine();
        }
        $this->engine = implode('.', $engines);

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function serialize($value)
    {
        $result = $value;
        foreach ($this->getSerializers() as $serializer) {
            $result = $serializer->serialize($result);
        }

        return $result;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function unserialize($value)
    {
        $result = $value;
        $serializers = $this->getSerializers();
        $count = count($serializers);
        for ($i = $count - 1; $i >= 0; $i--) {
            $result = $serializers[$i]->unserialize($result);
        }

        return $result;
    }
}
