<?php

namespace App\VBC\Http\Controllers\Diagnosis;

use App\Http\Controllers\Controller;
use App\VBC\Http\Requests\Diagnosis\SearchIcd10CodesRequest;
use App\VBC\Services\Diagnosis\Icd10SearchService;
use Illuminate\Http\JsonResponse;

class Icd10SearchController extends Controller
{
    public function __construct(private readonly Icd10SearchService $service)
    {
    }

    /** Typeahead for the "Diagnosis Code" field (PD-3498). */
    public function search(SearchIcd10CodesRequest $request): JsonResponse
    {
        return response()->json($this->service->search($request->terms()));
    }
}
