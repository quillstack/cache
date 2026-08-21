<?php

declare(strict_types=1);

namespace Quillstack\Cache\Tests\Unit;

use Quillstack\Cache\Exceptions\CacheException;
use Quillstack\Cache\FileCache;
use Quillstack\Cache\Clock\FrozenClock;
use Quillstack\LocalStorage\LocalStorage;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\AssertExceptions;
use Quillstack\UnitTests\Types\AssertBoolean;
use Quillstack\UnitTests\Types\AssertNull;

class TestFileCache
{
    private string $directory;
    private FileCache $cache;
    private FrozenClock $clock;

    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean,
        private AssertNull $assertNull,
        private AssertExceptions $assertExceptions
    ) {
        $this->directory = sys_get_temp_dir() . '/quillstack-cache-' . getmypid();
        $this->clock = new FrozenClock();
        $this->cache = new FileCache(new LocalStorage(), $this->directory, $this->clock);
        $this->cache->clear();
    }

    public function whatWasPutInSurvivesTheObjectThatWroteIt()
    {
        $this->cache->set('name', 'Radek');

        // A second cache over the same directory reads what the first one wrote, which is
        // the whole point of keeping it on disk.
        $another = new FileCache(new LocalStorage(), $this->directory, $this->clock);

        $this->assertEqual->equal('Radek', $another->get('name'));
        $this->assertBoolean->isTrue($another->has('name'));
    }

    public function anythingSerialisableCanBeKept()
    {
        $this->cache->set('array', ['a' => 1, 'b' => [2, 3]]);

        $this->assertEqual->equal(['a' => 1, 'b' => [2, 3]], $this->cache->get('array'));
    }

    public function aKeyNobodyPutInGivesTheDefault()
    {
        $this->assertNull->isNull($this->cache->get('nothing'));
        $this->assertEqual->equal('fallback', $this->cache->get('nothing', 'fallback'));
        $this->assertBoolean->isFalse($this->cache->has('nothing'));
    }

    /**
     * An entry which has run out is deleted as it is read, so the directory does not grow
     * with things nobody will ever get back.
     */
    public function anEntryWhichRanOutIsRemoved()
    {
        $this->cache->set('short', 'value', 60);
        $this->clock->sleep(61);

        $this->assertNull->isNull($this->cache->get('short'));
        $this->assertEqual->equal(0, count(glob($this->directory . '/*.cache') ?: []));
    }

    public function aKeyCanHoldWhateverAFileNameCannot()
    {
        $this->cache->set('a key with spaces and ünïcode', 'value');

        $this->assertEqual->equal('value', $this->cache->get('a key with spaces and ünïcode'));
    }

    public function entriesAreDeletedAndCleared()
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);

        $this->assertBoolean->isTrue($this->cache->delete('a'));
        $this->assertBoolean->isFalse($this->cache->has('a'));

        $this->cache->clear();
        $this->assertBoolean->isFalse($this->cache->has('b'));
    }

    public function deletingSomethingWhichIsNotThereIsFine()
    {
        $this->assertBoolean->isTrue($this->cache->delete('nothing'));
    }

    public function manyAtOnce()
    {
        $this->cache->setMultiple(['a' => 1, 'b' => 2]);

        $this->assertEqual->equal(
            ['a' => 1, 'b' => 2, 'c' => 'fallback'],
            $this->cache->getMultiple(['a', 'b', 'c'], 'fallback')
        );

        $this->cache->deleteMultiple(['a', 'b']);
        $this->assertBoolean->isFalse($this->cache->has('a'));
    }

    /**
     * A file holding something else than a cache entry is thrown away rather than trusted.
     */
    public function afileWhichIsNotAnEntryIsDiscarded()
    {
        $this->cache->set('key', 'value');
        $path = $this->directory . '/' . sha1('key') . '.cache';
        file_put_contents($path, 'not serialised at all');

        $this->assertNull->isNull($this->cache->get('key'));
        $this->assertBoolean->isFalse(file_exists($path));
    }

    public function adirectoryWhichCannotBeMadeIsReported()
    {
        $this->assertExceptions->expect(CacheException::class);

        (new FileCache(new LocalStorage(), "/quillstack\0impossible"))->set('key', 'value');
    }
}
