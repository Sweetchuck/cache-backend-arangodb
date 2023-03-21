<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

use DateInterval;

class Utils
{

    public static function getDataType(mixed $value): string
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }

    /**
     * @return mixed[]
     */
    public static function fetchAllValuesFromGenerator(\Generator $generator): array
    {
        $items = [];
        foreach ($generator as $item) {
            $items[] = $item;
        }

        return $items;
    }

    public static function secondsToDateInterval(null|int|float $seconds): \DateInterval
    {
        $interval = new DateInterval(sprintf('PT%dS', max(0, $seconds)));
        $interval->f = fmod($seconds, 1);

        return $interval;
    }
}
