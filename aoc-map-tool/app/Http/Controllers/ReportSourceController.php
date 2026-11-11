<?php

namespace App\Http\Controllers;

use App\Services\Reports\ReportSourceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportSourceController extends Controller
{
    public function __construct(private ReportSourceService $sourceService)
    {}

    public function getSource(Request $request): JsonResponse
    {
        $sourceType = $request->input('source_type', []);
        $sourceObj = $this->sourceService->getSource($sourceType);
        $sourceArr = [];

        foreach ($sourceObj as $source) {
            $sourceArr[] = [
                'value' => $source->appSource,
                'text' => $source->appSource,
            ];
        }

        return response()->json($sourceArr);
    }
}
