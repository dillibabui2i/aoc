<?php

namespace App\VBC\Common\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class VBCServiceProvider extends ServiceProvider
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
        Route::middleware(['api', 'auth', 'vbc.permission'])
            ->group(base_path('app/VBC/Http/routes.php'));
    }
}
