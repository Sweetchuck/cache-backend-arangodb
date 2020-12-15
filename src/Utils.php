<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

use DateInterval;

class Utils
{

    public static function getDataType($value): string
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }

    public static function fetchAllValuesFromGenerator(\Generator $generator): array
    {
        $items = [];
        foreach ($generator as $item) {
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param null|int|float $seconds
     */
    public static function secondsToDateInterval($seconds)
    {
        $interval = new DateInterval(sprintf('PT%dS', max(0, $seconds)));
        $interval->f = fmod($seconds, 1);

        return $interval;
    }
}
