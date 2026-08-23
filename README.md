# Quillstack Cache

[![Tests](https://github.com/quillstack/cache/actions/workflows/tests.yml/badge.svg)](https://github.com/quillstack/cache/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/quillstack/cache.svg)](https://packagist.org/packages/quillstack/cache)
[![Downloads](https://img.shields.io/packagist/dt/quillstack/cache.svg)](https://packagist.org/packages/quillstack/cache)
[![PHP Version](https://img.shields.io/packagist/php-v/quillstack/cache)](https://packagist.org/packages/quillstack/cache)
[![StyleCI](https://github.styleci.io/repos/1342146232/shield?branch=main)](https://github.styleci.io/repos/1342146232?branch=main)
[![CodeFactor](https://www.codefactor.io/repository/github/quillstack/cache/badge)](https://www.codefactor.io/repository/github/quillstack/cache)
[![License](https://img.shields.io/packagist/l/quillstack/cache)](https://github.com/quillstack/cache/blob/main/LICENSE)

A simple cache based on PSR-16: Common Interface for Caching Libraries.

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

## Time

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

## Keys

PSR-16 asks a key to hold at least one character and none of `{}()/\@:`. A key which does
not is refused with an `InvalidCacheKeyException`, which is a
`Psr\SimpleCache\InvalidArgumentException`.

## Tests

```shell
composer test
```

Coverage needs phpdbg:

```shell
composer test:coverage
```

## License

MIT. See [LICENSE](LICENSE).
