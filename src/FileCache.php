<?php

declare(strict_types=1);

namespace Quillstack\Cache;

use DateInterval;
use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheInterface;
use Quillstack\Cache\Exceptions\CacheException;
use Quillstack\Clock\SystemClock;
use Quillstack\StorageInterface\StorageInterface;
use Throwable;

/**
 * Keeps entries on disk, one file per key, so they outlive the request which wrote them.
 */
class FileCache implements CacheInterface
{
    private ClockInterface $clock;

    public function __construct(
        private readonly StorageInterface $storage,
        private readonly string $directory,
        ?ClockInterface $clock = null
    ) {
        $this->clock = $clock ?? new SystemClock();
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $entry = $this->read(Key::validate($key));

        return $entry === null ? $default : $entry['value'];
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->makeDirectory();

        return $this->storage->save($this->pathFor(Key::validate($key)), serialize([
            'value' => $value,
            'expiresAt' => Ttl::expiresAt($ttl, $this->clock->now()->getTimestamp()),
        ]));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $path = $this->pathFor(Key::validate($key));

        return !$this->storage->exists($path) || $this->storage->delete($path);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        foreach (glob($this->directory . '/*.cache') ?: [] as $path) {
            $this->storage->delete($path);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        foreach (Key::readMany($keys) as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    /**
     * {@inheritDoc}
     *
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach (Key::readMany($keys) as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return $this->read(Key::validate($key)) !== null;
    }

    /**
     * The entry behind a key, or null when there is none or it has run out. An entry which
     * has run out is deleted on the way, so the directory does not grow without end.
     *
     * @return array{value: mixed, expiresAt: ?int}|null
     */
    private function read(string $key): ?array
    {
        $path = $this->pathFor($key);

        if (!$this->storage->exists($path)) {
            return null;
        }

        $contents = $this->storage->get($path);
        $entry = is_string($contents) ? @unserialize($contents) : false;

        if (!is_array($entry) || !array_key_exists('value', $entry)) {
            $this->storage->delete($path);

            return null;
        }

        /** @var array{value: mixed, expiresAt: ?int} $entry */
        if ($entry['expiresAt'] !== null && $entry['expiresAt'] <= $this->clock->now()->getTimestamp()) {
            $this->storage->delete($path);

            return null;
        }

        return $entry;
    }

    /**
     * A key can hold anything a file name cannot, so the file is named after its hash and
     * the key itself is only inside.
     */
    private function pathFor(string $key): string
    {
        return $this->directory . '/' . sha1($key) . '.cache';
    }

    private function makeDirectory(): void
    {
        if (is_dir($this->directory)) {
            return;
        }

        try {
            if (!mkdir($this->directory, 0o775, true) && !is_dir($this->directory)) {
                throw new CacheException("Unable to create the cache directory: {$this->directory}");
            }
        } catch (Throwable $throwable) {
            throw new CacheException("Unable to create the cache directory: {$this->directory}", 0, $throwable);
        }
    }

}
