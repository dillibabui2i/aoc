<?php

namespace App\Providers;

use App\Helpers\H;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class RoleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::if('canPermission', function ($permission) {
            return H::canPermissions($permission);
        });

        Blade::if('canAnyPermission', function ($permissions) {
            return H::canPermissions($permissions);
        });
    }
}
