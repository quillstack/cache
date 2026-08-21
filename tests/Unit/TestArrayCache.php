<?php

declare(strict_types=1);

namespace Quillstack\Cache\Tests\Unit;

use DateInterval;
use Quillstack\Cache\Exceptions\InvalidCacheKeyException;
use Quillstack\Cache\ArrayCache;
use Quillstack\Clock\FrozenClock;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\AssertExceptions;
use Quillstack\UnitTests\Types\AssertBoolean;
use Quillstack\UnitTests\Types\AssertNull;

class TestArrayCache
{
    private ArrayCache $cache;
    private FrozenClock $clock;

    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean,
        private AssertNull $assertNull,
        private AssertExceptions $assertExceptions
    ) {
        $this->clock = new FrozenClock();
        $this->cache = new ArrayCache($this->clock);
    }

    public function whatWasPutInComesBackOut()
    {
        $this->cache->set('name', 'Radek');

        $this->assertEqual->equal('Radek', $this->cache->get('name'));
        $this->assertBoolean->isTrue($this->cache->has('name'));
    }

    public function aKeyNobodyPutInGivesTheDefault()
    {
        $this->assertNull->isNull($this->cache->get('nothing'));
        $this->assertEqual->equal('fallback', $this->cache->get('nothing', 'fallback'));
        $this->assertBoolean->isFalse($this->cache->has('nothing'));
    }

    public function anythingCanBeKept()
    {
        $this->cache->set('array', ['a' => 1]);
        $this->cache->set('null', null);
        $this->cache->set('false', false);

        $this->assertEqual->equal(['a' => 1], $this->cache->get('array'));

        // A null which was put in is not the same as a key which was never set.
        $this->assertNull->isNull($this->cache->get('null', 'fallback'));
        $this->assertEqual->equal(false, $this->cache->get('false', 'fallback'));
    }

    public function anEntryRunsOutAfterItsTime()
    {
        $this->cache->set('short', 'value', 60);

        $this->clock->sleep(59);
        $this->assertEqual->equal('value', $this->cache->get('short'));

        $this->clock->sleep(1);
        $this->assertNull->isNull($this->cache->get('short'));
        $this->assertBoolean->isFalse($this->cache->has('short'));
    }

    public function timeCanBeGivenAsAnInterval()
    {
        $this->cache->set('hour', 'value', new DateInterval('PT1H'));

        $this->clock->sleep(3599);
        $this->assertEqual->equal('value', $this->cache->get('hour'));

        $this->clock->sleep(1);
        $this->assertNull->isNull($this->cache->get('hour'));
    }

    public function withoutATimeAnEntryStays()
    {
        $this->cache->set('forever', 'value');
        $this->clock->sleep(10_000_000);

        $this->assertEqual->equal('value', $this->cache->get('forever'));
    }

    public function entriesAreDeletedAndCleared()
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);

        $this->assertBoolean->isTrue($this->cache->delete('a'));
        $this->assertBoolean->isFalse($this->cache->has('a'));
        $this->assertBoolean->isTrue($this->cache->has('b'));

        $this->cache->clear();
        $this->assertBoolean->isFalse($this->cache->has('b'));
    }

    public function deletingSomethingWhichIsNotThereIsFine()
    {
        $this->assertBoolean->isTrue($this->cache->delete('nothing'));
    }

    public function manyAtOnce()
    {
        $this->cache->setMultiple(['a' => 1, 'b' => 2], 60);

        $this->assertEqual->equal(
            ['a' => 1, 'b' => 2, 'c' => 'fallback'],
            $this->cache->getMultiple(['a', 'b', 'c'], 'fallback')
        );

        $this->cache->deleteMultiple(['a', 'b']);
        $this->assertBoolean->isFalse($this->cache->has('a'));
        $this->assertBoolean->isFalse($this->cache->has('b'));
    }

    public function anEmptyKeyIsRefused()
    {
        $this->assertExceptions->expect(InvalidCacheKeyException::class);

        $this->cache->set('', 'value');
    }

    public function aKeyWithAReservedCharacterIsRefused()
    {
        $this->assertExceptions->expect(InvalidCacheKeyException::class);

        $this->cache->get('user:1');
    }

    public function aListOfKeysHasToHoldStrings()
    {
        $this->assertExceptions->expect(InvalidCacheKeyException::class);

        $this->cache->getMultiple(['fine', 3]);
    }
}
