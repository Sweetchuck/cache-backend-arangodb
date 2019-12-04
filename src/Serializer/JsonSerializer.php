<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb\Serializer;

class JsonSerializer extends BaseSerializer
{

    /**
     * {@inheritdoc}
     */
    protected $engine = 'json';

    /**
     * @return $this
     */
    public function setOptions(array $options)
    {
        if (array_key_exists('encodeFlags', $options)) {
            $this->setEncodeFlags($options['encodeFlags']);
        }

        if (array_key_exists('encodeDepth', $options)) {
            $this->setEncodeFlags($options['encodeDepth']);
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
    /**
     * @var int
     */
    protected $encodeFlags = 0;

    public function getEncodeFlags(): int
    {
        return $this->encodeFlags;
    }

    /**
     * @return $this
     *
     * @see \json_encode()
     */
    public function setEncodeFlags(int $encodeFlags)
    {
        $this->encodeFlags = $encodeFlags;

        return $this;
    }
    // endregion

    // region encodeDepth
    /**
     * @var int
     */
    protected $encodeDepth = 512;

    public function getEncodeDepth(): int
    {
        return $this->encodeDepth;
    }

    /**
     * @return $this
     *
     * @see \json_encode()
     */
    public function setEncodeDepth(int $encodeDepth)
    {
        $this->encodeDepth = $encodeDepth;

        return $this;
    }
    // endregion

    // region decodeAssociative
    /**
     * @var bool
     */
    protected $decodeAssociative = true;

    public function getDecodeAssociative(): bool
    {
        return $this->decodeAssociative;
    }

    /**
     * @return $this
     *
     * @see \json_decode()
     */
    public function setDecodeAssociative(bool $decodeAssociative)
    {
        $this->decodeAssociative = $decodeAssociative;

        return $this;
    }
    // endregion

    // region decodeFlags
    /**
     * @var int
     */
    protected $decodeFlags = 0;

    public function getDecodeFlags(): int
    {
        return $this->decodeFlags;
    }

    /**
     * @return $this
     */
    public function setDecodeFlags(int $decodeFlags)
    {
        $this->decodeFlags = $decodeFlags;

        return $this;
    }
    // endregion

    // region decodeDepth
    /**
     * @var int
     */
    protected $decodeDepth = 512;

    public function getDecodeDepth(): int
    {
        return $this->decodeDepth;
    }

    /**
     * @return $this
     */
    public function setDecodeDepth(int $decodeDepth)
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
        return json_encode(
            $value,
            $this->getEncodeFlags(),
            $this->getEncodeDepth(),
        );
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    public function unserialize($value)
    {
        return json_decode(
            $value,
            $this->getDecodeAssociative(),
            $this->getDecodeDepth(),
            $this->getDecodeFlags(),
        );
    }
}
