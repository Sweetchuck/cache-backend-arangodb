<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

use Cache\Adapter\Common\PhpCacheItem;
use Sweetchuck\CacheBackend\ArangoDb\Validator\BasicValidator;

class CacheItem implements PhpCacheItem
{
    use NowTrait;

    protected string $key = '';

    protected bool $hasValue = false;

    protected mixed $value = null;

    protected ValidatorInterface $validator;

    protected ?\DateTimeInterface $created = null;

    /**
     * @param string[] $previousTags
     */
    public function __construct(
        int|float|string|\Stringable $key,
        ?array $previousTags = [],
        ?ValidatorInterface $validator = null,
    ) {
        $keyString = (string) $key;
        $this->validator = $validator ?: new BasicValidator();
        $this->validator->assertKey($keyString);

        $this->key = $keyString;
        $this->previousTags = $previousTags ?: [];
        $this->setTags($this->previousTags);
    }

    // region Implements - \Psr\Cache\CacheItemInterface
    protected ?\DateTimeInterface $expires = null;

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit(): bool
    {
        return $this->hasValue && $this->isAlive();
    }

    /**
     * {@inheritdoc}
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function set(mixed $value): static
    {
        $this->hasValue = true;
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        $this->expires = $expiration;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter(int|\DateInterval|null $time): static
    {
        if ($time === null) {
            $this->expires = null;
            $this->onExpiresChange();

            return $this;
        }

        if (is_int($time)) {
            $time = Utils::secondsToDateInterval($time);
        }

        $this->expires = $this->getNow()->add($time);
        $this->onExpiresChange();

        return $this;
    }
    // endregion

    // region Implements - \Cache\Adapter\Common\HasExpirationTimestampInterface
    /**
     * {@inheritdoc}
     */
    public function getExpirationTimestamp(): ?int
    {
        return $this->expires ?
            (int) $this->expires->format('U')
            : null;
    }
    // endregion

    // region Implements - \Cache\TagInterop\TaggableCacheItemInterface
    /**
     * @var string[]
     */
    protected array $previousTags = [];

    /**
     * @var string[]
     */
    protected array $tags = [];

    /**
     * {@inheritdoc}
     */
    public function getPreviousTags(): array
    {
        return $this->previousTags;
    }

    /**
     * {@inheritdoc}
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * {@inheritdoc}
     */
    public function setTags(iterable $tags): static
    {
        $this->validator->assertTags($tags);
        $this->tags = [];
        foreach ($tags as $tag) {
            $string = (string) $tag;
            $this->tags[$string] = $string;
        }

        return $this;
    }
    // endregion

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function getCreatedTimestamp(): ?float
    {
        return $this->created ? (float) $this->created->format('U.u') : null;
    }

    public function setCreated(?\DateTimeInterface $created): static
    {
        $this->created = $created;

        return $this;
    }

    public function setCreatedTimestamp(null|int|float $timestamp): static
    {
        if ($timestamp === null) {
            $this->created = null;

            return $this;
        }

        $created = \DateTime::createFromFormat('U.u', (string) $timestamp);
        if (!$created) {
            // @todo Error handling.
            return $this;
        }

        $this->created = $created;

        return $this;
    }

    public function isAlive(): bool
    {
        return $this->expires === null || $this->getExpirationTimestamp() > $this->getNowTimestamp();
    }

    /**
     * @internal This public method used by the CacheItemPool.
     *
     * @see \Sweetchuck\CacheBackend\ArangoDb\CacheItemPool::save
     */
    public function onSave(): static
    {
        $this->hasValue = true;

        return $this;
    }

    /**
     * @internal This public method used by the CacheItemPool.
     *
     * @see \Sweetchuck\CacheBackend\ArangoDb\CacheItemPool::getItemsFromStorage
     * @see \Sweetchuck\CacheBackend\ArangoDb\CacheItemPool::getItemsDeferred
     */
    public function onFetch(): static
    {
        $this->previousTags = $this->tags;
        $this->hasValue = true;

        return $this;
    }

    protected function onExpiresChange(): static
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
