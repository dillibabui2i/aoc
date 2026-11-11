<?php

namespace App\PrivateAPI\Http\Controllers\Providers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EditableController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class ProvidersProxyController extends Controller
{
    public function __construct(private readonly EditableController $editableController)
    {
    }

    public function updateCustomField(Request $request): Response
    {
        try {
            $result = $this->editableController->update($request);
            $data = json_decode($result->getContent(), true);

            if (empty($data['success'])) {
                return response()->noContent(Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return response()->noContent(Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->noContent(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
