<?php

declare(strict_types=1);

namespace Quillstack\Cache\Exceptions;

use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

class InvalidCacheKeyException extends CacheException implements PsrInvalidArgumentException
{
    //
}
