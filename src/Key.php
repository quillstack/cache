<?php

declare(strict_types=1);

namespace Quillstack\Cache;

use Quillstack\Cache\Exceptions\InvalidCacheKeyException;

/**
 * The rules PSR-16 puts on a key: at least one character, and none of the ones reserved for
 * whatever a cache is built on.
 */
final class Key
{
    /**
     * @var string
     */
    public const RESERVED = '{}()/\\@:';

    public static function validate(string $key): string
    {
        if ($key === '') {
            throw new InvalidCacheKeyException('A cache key cannot be empty');
        }

        if (strpbrk($key, self::RESERVED) !== false) {
            throw new InvalidCacheKeyException(
                'A cache key cannot hold any of ' . self::RESERVED . ", got: {$key}"
            );
        }

        return $key;
    }

    /**
     * Reads whatever was given as a list of keys, which PSR-16 allows to be any iterable.
     *
     * @param iterable<mixed> $keys
     *
     * @return string[]
     */
    public static function readMany(iterable $keys): array
    {
        $read = [];

        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidCacheKeyException('A cache key has to be a string');
            }

            $read[] = self::validate($key);
        }

        return $read;
    }
}
