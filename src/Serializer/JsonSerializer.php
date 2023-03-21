<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Serializer;

class JsonSerializer extends BaseSerializer
{

    protected string $engine = 'json';

    /**
     * @phpstan-param CacheBackendArangoDbJsonSerializerOptions $options
     */
    public function setOptions(array $options): static
    {
        if (array_key_exists('encodeFlags', $options)) {
            $this->setEncodeFlags($options['encodeFlags']);
        }

        if (array_key_exists('encodeDepth', $options)) {
            $this->setEncodeDepth($options['encodeDepth']);
        }

        if (array_key_exists('decodeAssociative', $options)) {
            $this->setDecodeAssociative($options['decodeAssociative']);
        }

        if (array_key_exists('decodeFlags', $options)) {
            $this->setDecodeFlags($options['decodeFlags']);
        }

        if (array_key_exists('decodeDepth', $options)) {
            $this->setDecodeDepth($options['decodeDepth']);
        }

        return $this;
    }

    // region encodeFlags
    protected int $encodeFlags = 0;

    public function getEncodeFlags(): int
    {
        return $this->encodeFlags;
    }

    /**
     * @see \json_encode()
     */
    public function setEncodeFlags(int $encodeFlags): static
    {
        $this->encodeFlags = $encodeFlags;

        return $this;
    }
    // endregion

    // region encodeDepth
    /**
     * @var int<1, max>
     */
    protected int $encodeDepth = 512;

    /**
     * @return int<1, max>
     */
    public function getEncodeDepth(): int
    {
        return $this->encodeDepth;
    }

    /**
     * @param int<1, max> $encodeDepth
     *
     * @see \json_encode()
     */
    public function setEncodeDepth(int $encodeDepth): static
    {
        $this->encodeDepth = $encodeDepth;

        return $this;
    }
    // endregion

    // region decodeAssociative
    protected bool $decodeAssociative = true;

    public function getDecodeAssociative(): bool
    {
        return $this->decodeAssociative;
    }

    /**
     * @see \json_decode()
     */
    public function setDecodeAssociative(bool $decodeAssociative): static
    {
        $this->decodeAssociative = $decodeAssociative;

        return $this;
    }
    // endregion

    // region decodeFlags
    protected int $decodeFlags = 0;

    public function getDecodeFlags(): int
    {
        return $this->decodeFlags;
    }

    public function setDecodeFlags(int $decodeFlags): static
    {
        $this->decodeFlags = $decodeFlags;

        return $this;
    }
    // endregion

    // region decodeDepth
    /**
     * @var int<1, max>
     */
    protected int $decodeDepth = 512;

    /**
     * @return int<1, max>
     */
    public function getDecodeDepth(): int
    {
        return $this->decodeDepth;
    }

    /**
     * @param  int<1, max> $decodeDepth
     */
    public function setDecodeDepth(int $decodeDepth): static
    {
        $this->decodeDepth = $decodeDepth;

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
        $result = json_encode(
            $value,
            $this->getEncodeFlags(),
            $this->getEncodeDepth(),
        );

        return $result === false ?
            '{}'
            : $result;
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($value)
    {
        return json_decode(
            (string) $value,
            $this->getDecodeAssociative(),
            $this->getDecodeDepth(),
            $this->getDecodeFlags(),
        );
    }
}
