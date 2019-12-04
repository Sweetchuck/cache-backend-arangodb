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
     * @param mixed $key
     * @param null|string $index
     *
     * @return string[]
     */
    public function validateKey($key, ?string $index = null): array;

    /**
     * @param mixed $key
     * @param null|string $index
     *
     * @return $this
     */
    public function assertKey($key, ?string $index = null);

    /**
     * @param array $keys
     *
     * @return $this
     */
    public function assertKeys($keys);

    /**
     * @param mixed $values
     *
     * @return string[]
     */
    public function validateValues($values): array;

    /**
     * @param mixed $values
     *
     * @return $this
     */
    public function assertValues($values);

    /**
     * @param mixed $tag
     * @param ?string $index
     *
     * @return string[]
     */
    public function validateTag($tag, ?string $index = null): array;

    /**
     * @param mixed $key
     *
     * @return $this
     */
    public function assertTag($key, ?string $index = null);

    /**
     * @param mixed $tags
     *
     * @return $this
     */
    public function assertTags($tags);

    /**
     * @param mixed $ttl
     *
     * @return string[]
     */
    public function validateTtl($ttl): array;

    /**
     * @param null|int|float|\DateInterval $ttl
     *
     * @return $this
     */
    public function assertTtl($ttl);
}
