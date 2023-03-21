<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Serializer;

/**
 * @psalm-import-type NativeSerializerUnserializeOptions from \Sweetchuck\CacheBackend\ArangoDb\PsalmTypes
 * @psalm-import-type NativeSerializerOptions            from \Sweetchuck\CacheBackend\ArangoDb\PsalmTypes
 */
class NativeSerializer extends BaseSerializer
{

    protected string $engine = 'native';

    /**
     * @phpstan-param CacheBackendArangoDbNativeSerializerOptions $options
     * @psalm-param NativeSerializerOptions $options
     */
    public function setOptions(array $options): static
    {
        if (array_key_exists('unserializeOptions', $options)) {
            $this->setUnserializeOptions($options['unserializeOptions']);
        }

        return $this;
    }

    // region unserializeOptions
    /**
     * @var array{allowed_classes?: array<array-key, string>|bool}
     */
    protected array $unserializeOptions = [];

    /**
     * @phpstan-return CacheBackendArangoDbNativeSerializerUnserializeOptions
     * @psalm-return NativeSerializerUnserializeOptions
     */
    public function getUnserializeOptions(): array
    {
        return $this->unserializeOptions;
    }

    /**
     * @param array{allowed_classes?: array<array-key, string>|bool} $options
     *
     * @see \unserialize()
     */
    public function setUnserializeOptions(array $options): static
    {
        $this->unserializeOptions = $options;

        return $this;
    }
    // endregion

    /**
     * {@inheritdoc}
     */
    public function serialize($value)
    {
        return serialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($value)
    {
        return unserialize($value, $this->getUnserializeOptions());
    }
}
