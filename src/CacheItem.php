<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

use Cache\Adapter\Common\PhpCacheItem;
use DateInterval;
use Sweetchuck\CacheBackend\ArangoDb\Validator\BasicValidator;

class CacheItem implements PhpCacheItem
{
    use NowTrait;

    /**
     * @var string
     */
    protected $key = '';

    /**
     * @var bool
     */
    protected $hasValue = false;

    /**
     * @var mixed
     */
    protected $value = null;

    /**
     * @var \Sweetchuck\CacheBackend\ArangoDb\ValidatorInterface
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    public function __construct($key, ?array $previousTags = [], ?ValidatorInterface $validator = null)
    {
        $this->validator = $validator ?: new BasicValidator();
        $this->validator->assertKey($key);

        $this->key = (string) $key;
        $this->previousTags = $previousTags ?: [];
        $this->setTags($this->previousTags);
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit()
    {
        return $this->hasValue && $this->isAlive();
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function set($value)
    {
        $this->hasValue = true;
        $this->value = $value;

        return $this;
    }

    /**
     * @var \DateTimeInterface|null
     */
    protected $expires = null;

    /**
     * {@inheritdoc}
     */
    public function expiresAt($expiration)
    {
        $this->expires = $expiration;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter($time)
    {
        if ($time === null) {
            $this->expires = null;
            $this->onExpiresChange();

            return $this;
        }

        if (is_int($time)) {
            $time = new DateInterval(sprintf('PT%dS', max(0, $time)));
        }

        $this->expires = $this->getNow()->add($time);
        $this->onExpiresChange();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpirationTimestamp()
    {
        return $this->expires ? (int) $this->expires->format('U') : null;
    }

    public function isAlive(): bool
    {
        return $this->expires === null || $this->getExpirationTimestamp() > $this->getNowTimestamp();
    }

    /**
     * @var string[]
     */
    protected $previousTags = [];

    /**
     * {@inheritdoc}
     */
    public function getPreviousTags()
    {
        return $this->previousTags;
    }

    /**
     * @var string[]
     */
    protected $tags = [];

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * {@inheritdoc}
     */
    public function setTags(array $tags)
    {
        $this->validator->assertTags($tags);
        $this->tags = [];
        foreach ($tags as $tag) {
            $this->tags[$tag] = $tag;
        }

        return $this;
    }

    /**
     * @internal This public method used by the CacheItemPool.
     */
    public function onSave()
    {
        $this->hasValue = true;

        return $this;
    }

    /**
     * @internal This public method used by the CacheItemPool.
     */
    public function onFetch()
    {
        $this->previousTags = $this->tags;
        $this->hasValue = true;

        return $this;
    }

    protected function onExpiresChange()
    {
        if ($this->expires === null) {
            return $this;
        }

        if ($this->getExpirationTimestamp() <= $this->getNowTimestamp()) {
            $this->hasValue = false;
        }

        return $this;
    }
}
