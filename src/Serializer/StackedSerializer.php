<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Serializer;

use Sweetchuck\CacheBackend\ArangoDb\SerializerInterface;

class StackedSerializer extends BaseSerializer
{

    /**
     * @var \Sweetchuck\CacheBackend\ArangoDb\SerializerInterface[]
     */
    protected array $serializers = [];

    /**
     * @return \Sweetchuck\CacheBackend\ArangoDb\SerializerInterface[]
     */
    public function getSerializers(): array
    {
        return $this->serializers;
    }

    /**
     * @param \Sweetchuck\CacheBackend\ArangoDb\SerializerInterface[] $serializers
     */
    public function setSerializers(array $serializers): static
    {
        $this->serializers = array_values($serializers);
        $this->updateEngine();

        return $this;
    }

    public function addSerializer(SerializerInterface $serializer): static
    {
        $this->serializers[] = $serializer;
        $this->updateEngine();

        return $this;
    }

    public function __construct(SerializerInterface ...$serializers)
    {
        $this->setSerializers($serializers);
    }

    protected function updateEngine(): static
    {
        $engines = [];
        foreach ($this->getSerializers() as $serializer) {
            $engines[] = $serializer->getEngine();
        }
        $this->engine = implode('.', $engines);

        return $this;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
