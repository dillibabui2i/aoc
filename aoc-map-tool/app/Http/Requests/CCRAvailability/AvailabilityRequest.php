<?php

namespace App\Http\Requests\CCRAvailability;

use App\Helpers\H;
use App\Http\Requests\CoreRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

abstract class AvailabilityRequest extends CoreRequest
{
    public const MANAGE_PERMISSION = 'ccrAvailability.manageOthers';

    public function authorize(): bool
    {
        return H::canPermissions(self::MANAGE_PERMISSION);
    }

    /** Preserves the {success, message} JSON envelope the frontend already expects, instead of Laravel's default 403 body. */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json(['success' => 0, 'message' => 'You do not have permission to manage PRC availability.'], Response::HTTP_FORBIDDEN)
        );
    }
}
