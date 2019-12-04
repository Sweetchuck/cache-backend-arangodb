<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

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
}
