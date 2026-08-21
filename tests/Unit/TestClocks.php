<?php

declare(strict_types=1);

namespace Quillstack\Cache\Tests\Unit;

use DateTimeImmutable;
use Quillstack\Cache\ArrayCache;
use Quillstack\Cache\Clock\FrozenClock;
use Quillstack\Cache\Clock\SystemClock;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestClocks
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    public function theSystemClockReadsTheWallClock()
    {
        $before = time();
        $now = (new SystemClock())->now();

        $this->assertBoolean->isTrue($now->getTimestamp() >= $before);
        $this->assertBoolean->isTrue($now->getTimestamp() <= time());
    }

    public function afrozenClockStandsStillUntilItIsMoved()
    {
        $clock = new FrozenClock(new DateTimeImmutable('@1700000000'));

        $this->assertEqual->equal(1700000000, $clock->now()->getTimestamp());
        $this->assertEqual->equal(1700000000, $clock->now()->getTimestamp());
        $this->assertEqual->equal(1700000060, $clock->sleep(60)->now()->getTimestamp());
    }

    /**
     * Without one, a cache reads the wall clock.
     */
    public function acacheBuiltWithoutAClockStillWorks()
    {
        $cache = new ArrayCache();
        $cache->set('key', 'value', 60);

        $this->assertEqual->equal('value', $cache->get('key'));
    }
}
