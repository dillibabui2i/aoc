<?php

namespace App\PrivateAPI\Http\Controllers\NHT;

use App\Http\Controllers\ClientController;
use App\Http\Controllers\Controller;
use App\PrivateAPI\Support\ScheduleRemoval;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class NTHProxyController extends Controller
{
    public function __construct(private readonly ClientController $clientController)
    {
    }

    public function save(Request $request): Response
    {
        try {
            $this->clientController
                ->saveBuildScheduleForm(
                    Request::create(
                        '/',
                        'POST',
                        ['d' => $this->buildPayload($request)->toFormFields()]
                    )
                );

            return response()->noContent(Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->noContent(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function buildPayload(Request $request): ScheduleRemoval
    {
        return new ScheduleRemoval(
            (int)$request->input('nthId'),
            (array)$request->input('shiftIds', [])
        );
    }
}
