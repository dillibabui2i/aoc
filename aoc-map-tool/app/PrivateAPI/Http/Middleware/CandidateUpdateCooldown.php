<?php

namespace App\PrivateAPI\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class CandidateUpdateCooldown
{
    private const DECAY_SECONDS = 2;
    private const MAX_ATTEMPTS = 1;

    public function handle(Request $request, Closure $next): Response
    {
        $key = 'candidate-core-update:' . $request->route('id');

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return response()->json([
                'success' => false,
                'message' => 'This candidate was just updated. Please wait before sending another update.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        return $next($request);
    }
}
