<?php

declare(strict_types = 1);

namespace Sweetchuck\CacheBackend\ArangoDb;

interface ValidatorInterface
{
    const PSR16_KEY_REGEX_PATTERN = '/^[^\\{\\}\\(\\)\/\\\\@\\:]+$/';

    const PSR16_KEY_REGEX_SHOULD_MATCH = true;

    const PSR16_TAG_REGEX_PATTERN = '/^[^\\{\\}\\(\\)\/\\\\@\\:]+$/';

    const PSR16_TAG_REGEX_SHOULD_MATCH = true;

    /**
     * @todo I think the key can be only string.
     *
     * @return string[]
     */
    public function validateKey(int|string $key, ?string $index = null): array;

    public function assertKey(int|string $key, ?string $index = null): static;

    /**
     * @param iterable<string> $keys
     */
    public function assertKeys(iterable $keys): static;

    /**
     * @param iterable<mixed> $values
     *
     * @return string[]
     */
    public function validateValues(iterable $values): array;

    /**
     * @param iterable<mixed> $values
     */
    public function assertValues(iterable $values): static;

    /**
     * @return string[]
     */
    public function validateTag(string $tag, ?string $index = null): array;

    public function assertTag(string $key, ?string $index = null): static;

    /**
     * @param iterable<mixed> $tags
     */
    public function assertTags(iterable $tags): static;

    /**
     * @return string[]
     */
    public function validateTtl(null|int|float|\DateInterval $ttl): array;

    public function assertTtl(null|int|float|\DateInterval $ttl): static;
}
