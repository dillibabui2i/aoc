<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class CacheKeysHelper
 *
 * @package App\Facades\CacheKeysHelper
 * @method static string generateKey(mixed ...$args)
 * @method static string generateHashedKey(mixed ...$args)
 * @see CacheKeysGeneratorService
 */
class CacheKeysHelper extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cache-keys-helper';
    }
}
