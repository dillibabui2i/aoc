<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PreventDuplicateScheduleSubmit
{
    private const DECAY_SECONDS = 60;
    private const IGNORED_FIELDS = ['_token', 'needs_to_review_expanded'];

    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->signature($request);

        if (!Cache::add($key, true, self::DECAY_SECONDS)) {
            return response()->json([
                'success' => 0,
                'error' => 'duplicate_submit',
                'message' => 'This was just saved. Please wait a moment before saving it again, '
                    . 'and refresh the page to see the record.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $response = $next($request);

        if ($this->shouldReleaseLock($response)) {
            Cache::forget($key);
        }

        return $response;
    }

    private function shouldReleaseLock(Response $response): bool
    {
        if ($response->isServerError()) {
            return false;
        }

        if (!$response->isSuccessful()) {
            return true;
        }

        $body = json_decode((string) $response->getContent(), true);

        if (!is_array($body)) {

            return false;
        }

        return !empty($body['error']) || (isset($body['success']) && (int) $body['success'] !== 1);
    }

    private function signature(Request $request): string
    {
        $payload = Arr::except($request->all(), self::IGNORED_FIELDS);
        ksort($payload);

        return sprintf(
            'schedule-next-submit:%s:%s',
            Auth::id() ?? $request->ip(),
            md5(serialize($payload))
        );
    }
}
