<?php

namespace App\Http\Middleware;

use App\Enums\AuthSourceEnum;
use App\Enums\ExternalUser\ExternalUserStatusEnum;
use App\Models\ExternalUserPatients;
use App\Models\ExternalUsers;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ExternalApiContext
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->attributes->get('auth_source') === AuthSourceEnum::SITE->value) {
            return $next($request);
        }

        /** @var ExternalUsers|null $user */
        $user = $request->user();

        if (!$user) {
            throw new AuthenticationException('Unauthenticated');
        }

        if ((int) $user->status !== ExternalUserStatusEnum::ACTIVE->value) {
            throw new AuthenticationException(__('messages.user_inactive'));
        }

        $patientIdRoutes = [
            'clientdetails',
            'getNTHSchedules',
            'getNThcheklist',
            'getUpcomingAppointments',
            'submitformrequest'
        ];

        $routeName = optional($request->route())->getName();

        if (in_array($routeName, $patientIdRoutes, true)) {
            $userPatients = $user->externalUserPatients->pluck('aoc_patient_id')->toArray();

            if (empty($userPatients)) {
                throw new UnauthorizedHttpException('Bearer', __('messages.invalid_patient'));
            }
        }

        ///todo improve me
        $request->merge([
            'id' => $user->id,
            'aoc_patient_id' => $user->aoc_patient_id, //todo need to check and remove
            'patients_ids' => $userPatients ?? [],
            'aoc_provider_id' => $user->aoc_provider_id,
            'language' => $request->headers->get('Accept-Language', null)
        ]);

        return $next($request);
    }
}
