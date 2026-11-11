<?php

namespace App\VBC\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\VBC\Http\Requests\Common\GetFiltersRequest;
use App\VBC\Services\Common\FiltersService;
use Illuminate\Http\JsonResponse;

class FiltersController extends Controller
{
    public function __construct(private readonly FiltersService $filtersService)
    {}

    public function index(GetFiltersRequest $request): JsonResponse
    {
        return response()->json($this->filtersService->getOptions($request->toDto()->bucket));
    }
}
