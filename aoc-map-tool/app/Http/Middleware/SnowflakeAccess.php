<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SnowflakeAccess
{
    private const ALLOWED_EMAILS = [
        'andrii.boichenko@angelsofcare.com',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        if (!Auth::check() || !in_array(Auth::user()->email, self::ALLOWED_EMAILS)) {
//            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
