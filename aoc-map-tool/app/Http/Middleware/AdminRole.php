<?php

namespace App\Http\Middleware;

use App\Helpers\H;
use App\Helpers\LD;
use Closure;
use Illuminate\Support\Facades\Auth;

class AdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(LD::checkFeatureFlag('force-logout-disabled-user')) {
            if (Auth::user()->status == 2) {
                Auth::logout();
            }
        }
        if (is_object(\Request::route()) && !H::canPermissions(\Request::route()->getName(),'route')) {
            return redirect(route('restricted'))->withErrors('This action is unauthorized.');
        } else {
            return $next($request);
        }
    }
}
