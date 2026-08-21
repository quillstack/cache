<?php

declare(strict_types=1);

namespace Quillstack\Cache;

use DateInterval;
use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheInterface;
use Quillstack\Cache\Clock\SystemClock;

/**
 * Keeps entries for as long as the process runs, which is what a single request needs and
 * what a test wants instead of touching a disk.
 */
class ArrayCache implements CacheInterface
{
    /**
     * @var array<string, array{value: mixed, expiresAt: ?int}>
     */
    private array $entries = [];

    private ClockInterface $clock;

    public function __construct(?ClockInterface $clock = null)
    {
        $this->clock = $clock ?? new SystemClock();
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        Key::validate($key);

        if (!$this->has($key)) {
            return $default;
        }

        return $this->entries[$key]['value'];
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->entries[Key::validate($key)] = [
            'value' => $value,
            'expiresAt' => Ttl::expiresAt($ttl, $this->clock->now()->getTimestamp()),
        ];

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        unset($this->entries[Key::validate($key)]);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        $this->entries = [];

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
        Key::validate($key);

        if (!isset($this->entries[$key])) {
            return false;
        }

        $expiresAt = $this->entries[$key]['expiresAt'];

        if ($expiresAt !== null && $expiresAt <= $this->clock->now()->getTimestamp()) {
            unset($this->entries[$key]);

            return false;
        }

        return true;
    }

}
