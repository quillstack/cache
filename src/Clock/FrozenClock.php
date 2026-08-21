<?php

declare(strict_types=1);

namespace Quillstack\Cache\Clock;

use DateInterval;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * A clock which stands still until it is moved, so a test can watch an entry run out
 * without waiting for it to.
 */
final class FrozenClock implements ClockInterface
{
    private DateTimeImmutable $now;

    public function __construct(?DateTimeImmutable $now = null)
    {
        $this->now = $now ?? new DateTimeImmutable('@1000000');
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function sleep(int $seconds): self
    {
        $this->now = $this->now->add(new DateInterval("PT{$seconds}S"));

        return $this;
    }
}
