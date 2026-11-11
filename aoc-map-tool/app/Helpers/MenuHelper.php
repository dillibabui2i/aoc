<?php

namespace App\Helpers;

use DB;
use Illuminate\Support\Arr;

class MenuHelper
{
    /**
     * @param string|null $route
     * @return string
     */
    public static function isActiveMenuItem(?string $route): string
    {
        return request()->routeIs($route) ? 'active' : '';
    }

    /**
     * @param string|null $route
     * @param string $group
     * @return string
     */
    public static function isActiveSubMenu(?string $route, string $group = '')
    {
        $routeList = !empty($group) ? config('menu.relatedRoutes.' . $group, []) : config('menu.relatedRoutes', []);
        $routes = Arr::flatten($routeList);

        return in_array($route, $routes, true) ? 'show' : '';
    }
}
