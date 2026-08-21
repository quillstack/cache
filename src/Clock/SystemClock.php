<?php

declare(strict_types=1);

namespace Quillstack\Cache\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * The clock on the wall.
 */
final class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
