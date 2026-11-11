<?php

namespace App\PrivateAPI\Http\Controllers\Candidates;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CandidatesController;
use App\Http\Controllers\EditableController;
use App\Http\Requests\Candidates\UpdateCandidateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class CandidatesProxyController extends Controller
{
    public function __construct(
        private readonly CandidatesController $candidatesController,
        private readonly EditableController $editableController,
    ) {
    }

    public function updateField(UpdateCandidateRequest $request): Response
    {
        try {
            $this->candidatesController->updateField($request);

            return response()->noContent(Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->noContent(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request)
    {
        try {
            $this->editableController->update($request);

            return response()->noContent(Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->noContent(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
