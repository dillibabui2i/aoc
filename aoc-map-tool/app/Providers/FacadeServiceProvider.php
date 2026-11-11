<?php

namespace App\Providers;

use App\Services\Utils\CacheKeysGeneratorService;
use Illuminate\Support\ServiceProvider;

/**
 * Class FacadeServiceProvider
 *
 * @package App\Providers\FacadeServiceProvider
 */
class FacadeServiceProvider extends ServiceProvider
{
    private const CACHE_KEYS_FACADE = 'cache-keys-helper';

    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(self::CACHE_KEYS_FACADE, function ($app) {
            return new CacheKeysGeneratorService();
        });
    }
}
