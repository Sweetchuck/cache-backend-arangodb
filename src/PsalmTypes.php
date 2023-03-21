<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

/**
 * @psalm-type ConnectionOptions = array<string, mixed>
 *
 * @psalm-type NativeSerializerUnserializeOptions = array{
 *   allowed_classes?: array<array-key, string>|bool,
 * }
 *
 * @psalm-type NativeSerializerOptions = array{
 *   unserializeOptions?: NativeSerializerUnserializeOptions,
 * }
 *
 * @psalm-type ExecuteStatementData = array{
 *   batchSize: int,
 *   sanitize: bool,
 * }
 */
class PsalmTypes
{
}
