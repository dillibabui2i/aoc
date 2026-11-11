<?php

namespace App\PrivateAPI\Factories;

use App\PrivateAPI\Http\Requests\ApiCoreRequest;
use App\PrivateAPI\Http\Requests\StoreNotesRequest;
use App\PrivateAPI\Http\Requests\Trackers\CreateTrackerRequest;
use App\PrivateAPI\Http\Requests\Trackers\UpdateTrackerRequest;
use App\PrivateAPI\Http\Requests\UpdateCandidateCustomFieldRequest;
use App\PrivateAPI\Http\Requests\UpdateCandidateRequest;
use App\PrivateAPI\Http\Requests\UpdateProviderPreferenceRequest;
use App\PrivateAPI\Http\Requests\UpdateScheduleFormRequest;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ApiRequestFactory
{
    private const INVALID_ARGUMENT_MSG_PATTERN = 'API request class [%s] must extend %s.';

    public function make(Request $request): ?ApiCoreRequest
    {
        $requestClass = $this->resolveRequestClass($request);

        if (!$requestClass) {
            return null;
        }

        $this->isRequestClassValid($requestClass);

        return $this->initRequest($requestClass, $request);
    }

    /**
     * @param Request $request
     *
     * @return string|null
     *
     * Define a validation class for route by route name
     */
    private function resolveRequestClass(Request $request): ?string
    {
        return match ($request->route()?->getName()) {
            'api.saveNotes' => StoreNotesRequest::class,
            'providers-proxy.custom-field.update' => UpdateProviderPreferenceRequest::class,
            'candidates-proxy.update-field' => UpdateCandidateCustomFieldRequest::class,
            'candidates-proxy.update' => UpdateCandidateRequest::class,
            'nth-proxy.save' => UpdateScheduleFormRequest::class,
            'trackers-proxy.create' => CreateTrackerRequest::class,
            'trackers-proxy.save' => UpdateTrackerRequest::class,
            default => null,
        };
    }

    private function isRequestClassValid(string $requestClass): void
    {
        if (!is_subclass_of($requestClass, ApiCoreRequest::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    self::INVALID_ARGUMENT_MSG_PATTERN,
                    $requestClass,
                    ApiCoreRequest::class
                )
            );
        }
    }

    private function initRequest(string $requestClass, Request $request): ApiCoreRequest
    {
        /** @var ApiCoreRequest $formRequest */
        $formRequest = $requestClass::createFromBase($request);

        $formRequest->setContainer(app());
        $formRequest->setRedirector(app('redirect'));
        $formRequest->setRouteResolver($request->getRouteResolver());
        $formRequest->setUserResolver($request->getUserResolver());

        return $formRequest;
    }

}
