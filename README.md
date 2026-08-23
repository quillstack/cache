# Quillstack Cache

[![Tests](https://github.com/quillstack/cache/actions/workflows/tests.yml/badge.svg)](https://github.com/quillstack/cache/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/quillstack/cache.svg)](https://packagist.org/packages/quillstack/cache)
[![Downloads](https://img.shields.io/packagist/dt/quillstack/cache.svg)](https://packagist.org/packages/quillstack/cache)
[![PHP Version](https://img.shields.io/packagist/php-v/quillstack/cache)](https://packagist.org/packages/quillstack/cache)
[![StyleCI](https://github.styleci.io/repos/1342146232/shield?branch=main)](https://github.styleci.io/repos/1342146232?branch=main)
[![CodeFactor](https://www.codefactor.io/repository/github/quillstack/cache/badge)](https://www.codefactor.io/repository/github/quillstack/cache)
[![Quality Gate](https://sonarcloud.io/api/project_badges/measure?project=quillstack_cache&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=quillstack_cache)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=quillstack_cache&metric=coverage)](https://sonarcloud.io/summary/new_code?id=quillstack_cache)
[![Maintainability](https://sonarcloud.io/api/project_badges/measure?project=quillstack_cache&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_cache)
[![Reliability](https://sonarcloud.io/api/project_badges/measure?project=quillstack_cache&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_cache)
[![Security](https://sonarcloud.io/api/project_badges/measure?project=quillstack_cache&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_cache)
[![License](https://img.shields.io/packagist/l/quillstack/cache)](https://github.com/quillstack/cache/blob/main/LICENSE)

A simple cache based on PSR-16: Common Interface for Caching Libraries.

## Why this exists

PSR-16 is a small interface and this is a small implementation of it: values in an array for one
request, or values in files for longer than that. There is no tagging, no pruning, no marshaller
to configure and no adapter for anything that is not on this machine.

What it does have is the behaviour a cache is supposed to have when something goes wrong. **An
entry that cannot be read is a miss**, not an error — a truncated file, an empty one, a value
whose expiry has passed, all give back the default you asked for. A cache that throws is a cache
that turns a slow request into a failed one.

## Requirements

- PHP 8.1 or newer

## Installation

```shell
composer require quillstack/cache
```

## Usage

Two caches come with it. `ArrayCache` keeps entries for as long as the process runs, which
is what a single request needs:

```php
use Quillstack\Cache\ArrayCache;

$cache = new ArrayCache();
$cache->set('user.42', $user, 300);

$user = $cache->get('user.42', $default);
```

`FileCache` writes them to disk, so they outlive the request:

```php
use Quillstack\Cache\FileCache;
use Quillstack\LocalStorage\LocalStorage;

$cache = new FileCache(new LocalStorage(), __DIR__ . '/var/cache');
```

How long an entry lives is given in seconds, as a `DateInterval`, or left out to keep it for
as long as the cache does:

```php
$cache->set('key', $value, 60);
$cache->set('key', $value, new DateInterval('PT1H'));
$cache->set('key', $value);
```

`getMultiple()`, `setMultiple()` and `deleteMultiple()` work on several keys at once,
`has()` says whether a key is there, and `clear()` empties the cache.

### Time

Both caches take a PSR-20 clock, which is what decides when an entry has run out. Without
one they read the wall clock. `FrozenClock` stands still until it is moved, so a test does
not have to wait:

```php
use Quillstack\Cache\Clock\FrozenClock;

$clock = new FrozenClock();
$cache = new ArrayCache($clock);

$cache->set('key', 'value', 60);
$clock->sleep(61);

$cache->get('key'); // null
```

### Keys

PSR-16 asks a key to hold at least one character and none of `{}()/\@:`. A key which does
not is refused with an `InvalidCacheKeyException`, which is a
`Psr\SimpleCache\InvalidArgumentException`.

## Benchmark

Measured with [quillstack/benchmark](https://github.com/quillstack/benchmark) on a thousand
`set()` and `get()` pairs, each storing a small array. Runs are interleaved and unconcurrent,
each figure is the median of five, and PHP is 8.5.7.

Only one other library is in the table. `laminas/laminas-cache` does not support PHP 8.5, and
`cache/filesystem-adapter` is still on PSR-16 version 1, so neither could be installed beside
this — which is worth knowing about them either way.

| | Version |
| --- | --- |
| quillstack/cache | 0.6.0 |
| symfony/cache | v7.4.17 |

**In memory, for the length of one request:**

| | Per set and get | Relative |
| --- | --- | --- |
| **quillstack/cache**, `ArrayCache` | **0.82 µs** | — |
| symfony/cache, `ArrayAdapter` | 1.36 µs | 1.7× |

**In files, for longer than that:**

| | Per set and get | Relative |
| --- | --- | --- |
| **quillstack/cache**, `FileCache` | **96.5 µs** | — |
| symfony/cache, `FilesystemAdapter` | 211.4 µs | 2.2× |

**The file difference is a guarantee, not an inefficiency.** Symfony writes each entry to a
temporary file and renames it over the old one, so a reader can never see a half-written entry.
This one writes the file directly, so a reader arriving mid-write can — and gets a miss, because
an entry that cannot be read is treated as one. For a cache that is the right outcome; if you
need the stronger guarantee, Symfony's is worth its 115 microseconds.

Symfony's adapter also has cache tags, pruning, a configurable marshaller, and adapters for
Redis, Memcached, APCu, PDO and more. This has an array and a directory.

## Tests

```shell
composer test
```

Coverage needs phpdbg:

```shell
composer test:coverage
```

## The rest of Quillstack

This is one component of [Quillstack](https://github.com/quillstack), a PHP framework which is
as simple to use as it is strict about what it does.

- [quillstack/local-storage](https://github.com/quillstack/local-storage) — what writes the files
- [quillstack/clock](https://github.com/quillstack/clock) — what decides whether an entry expired
- [quillstack/framework](https://github.com/quillstack/framework) — where a cache is wired in

## License

MIT. See [LICENSE](LICENSE).
