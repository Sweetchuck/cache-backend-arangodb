<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

use DateTime;
use DateTimeInterface;

trait NowTrait
{
    /**
     * @var string
     */
    protected $nowClass = DateTime::class;

    public function getNowClass(): string
    {
        return $this->nowClass;
    }

    /**
     * @return $this
     */
    public function setNowClass(string $nowClass)
    {
        $this->nowClass = $nowClass;

        return $this;
    }

    public function getNow(): DateTimeInterface
    {
        $nowClass = $this->getNowClass();

        return new $nowClass();
    }

    protected function getNowTimestamp(): int
    {
        return (int) $this->getNow()->format('U');
    }
}
