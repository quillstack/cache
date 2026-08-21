<?php

declare(strict_types=1);

namespace Quillstack\Cache;

use DateInterval;
use DateTimeImmutable;

/**
 * How long an entry lives, given as seconds, as a DateInterval, or as null for as long as
 * the cache keeps it.
 */
final class Ttl
{
    /**
     * The moment an entry stops being valid, or null when it never does.
     */
    public static function expiresAt(null|int|DateInterval $ttl, int $now): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof DateInterval) {
            $reference = new DateTimeImmutable('@0');

            return $now + ($reference->add($ttl)->getTimestamp() - $reference->getTimestamp());
        }

        return $now + $ttl;
    }
}
