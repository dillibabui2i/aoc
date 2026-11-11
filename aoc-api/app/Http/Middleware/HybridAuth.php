<?php

namespace App\Http\Middleware;

use App\Enums\AuthSourceEnum;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HybridAuth
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next): JsonResponse|RedirectResponse
    {
        /*** AL/Other API ****/
        if ($request->hasAny(['api_key', 'api_secret'])) {
            if (
                $request->api_key === config('auth.al_api_key') &&
                $request->api_secret === config('auth.al_api_secret')
            ) {
                $request->attributes->set('auth_source', AuthSourceEnum::SITE->value);
                return $next($request);
            }
        }

        /*** Mobile API ****/
        if (auth('sanctum')->check()) {
            $request->attributes->set('auth_source', AuthSourceEnum::MOBILE->value);
            return $next($request);
        }

        throw new AuthenticationException('Unauthenticated');
    }
}
