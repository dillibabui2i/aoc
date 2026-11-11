<?php

namespace App\PrivateAPI\Common\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PrivateAPIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        Route::middleware(['api'])
            ->prefix('api')
            ->group(base_path('app/PrivateAPI/Http/routes.php'));
    }
}
