<?php

namespace App\PrivateAPI\Services;

use App\PrivateAPI\Factories\ApiRequestFactory;
use Illuminate\Http\Request;

class ApiValidationService
{
    public function __construct(private ApiRequestFactory $requestFactory)
    {}

    public function validateRequest(Request $request): void
    {
        $apiRequest = $this->requestFactory->make($request);

        if (!$apiRequest) {
            return;
        }

        $apiRequest->validateResolved();

        $request->replace($apiRequest->all());
    }

}
