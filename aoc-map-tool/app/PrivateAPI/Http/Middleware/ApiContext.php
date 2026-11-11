<?php

namespace App\PrivateAPI\Http\Middleware;

use App\PrivateAPI\Services\ApiValidationService;
use App\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use JsonException;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

class ApiContext
{
    public function __construct(
        private readonly ApiValidationService $validationService,
        private readonly User $userModel,
    ) {
    }

    /**
     * @throws JsonException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction()) {
            return response()->json([], Response::HTTP_FORBIDDEN);
        }

        $clientId = $this->extractClientId($request);
        $user = $this->userModel->getAPIUserByClientID($clientId);

        if (!$user) {
            return response('API user not found.', Response::HTTP_UNAUTHORIZED);
        }

        $request->headers->set('Accept', 'application/json');

        Auth::guard('web')->setUser($user);
        $this->validationService->validateRequest($request);

        return $next($request);
    }

    private function extractClientId(Request $request): string
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            return response('Bearer token is missing.', Response::HTTP_UNAUTHORIZED);
        }

        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return response('Invalid JWT format.', Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode(
            $this->decodeBase64Url($parts[1]),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $clientId = $payload['client_id'] ?? null;

        if (!$clientId) {
            return response('Client ID is missing from the token.', Response::HTTP_UNAUTHORIZED);
        }

        return $clientId;
    }

    private function decodeBase64Url(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $value .= str_repeat('=', (4 - strlen($value) % 4) % 4);

        $decoded = base64_decode($value, strict: true);

        if ($decoded === false) {
            throw new UnexpectedValueException('Invalid JWT payload encoding.');
        }

        return $decoded;
    }
}