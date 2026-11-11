<?php

namespace App\VBC\Http\Middleware;

use App\Helpers\UserPermissionResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

readonly class EnsurePermission
{
    public function __construct(private UserPermissionResolver $permissionResolver)
    {}

    public function handle(Request $request, Closure $next)
    {
        $routeName = Route::currentRouteName();

        if ($routeName && !$this->permissionResolver->isRouteAllowedForUser($routeName)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => "You don't have permission to perform this action."], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('restricted')->withErrors('This action is unauthorized.');
        }

        return $next($request);

    }
}
