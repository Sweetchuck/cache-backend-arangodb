<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

use ArangoDBClient\Document;

/**
 * @property string $key
 * @property mixed $value
 * @property null|int|string $expires
 * @property string[] $tags
 * @property float $created
 */
class CacheDocument extends Document
{
}
