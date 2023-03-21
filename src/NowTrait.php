<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

trait NowTrait
{
    protected string $nowClass = \DateTime::class;

    public function getNowClass(): string
    {
        return $this->nowClass;
    }

    public function setNowClass(string $nowClass): static
    {
        $this->nowClass = $nowClass;

        return $this;
    }

    public function getNow(): \DateTimeInterface
    {
        /** @var \DateTimeInterface $nowClass */
        $nowClass = $this->getNowClass();

        return new $nowClass();
    }

    protected function getNowTimestamp(): int
    {
        return (int) $this->getNow()->format('U');
    }
}
