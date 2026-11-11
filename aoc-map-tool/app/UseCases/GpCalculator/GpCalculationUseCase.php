<?php

namespace App\UseCases\GpCalculator;

use App\Helpers\LD;
use App\Services\GpCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final readonly class GpCalculationUseCase
{
    public function __construct(private GpCalculator\GpCalculatorService $calculator)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $calculatorJson = $request->get('calculator') ?? null;

        if (!$calculatorJson || !is_string($calculatorJson)) {
            return new JsonResponse(['status' => false, 'message' => 'No source data for calculation'], Response::HTTP_BAD_REQUEST);
        }
        $data = json_decode($calculatorJson, true);

        if (!is_array($data)
            || !isset($data['clients']) || !is_array($data['clients'])
            || !isset($data['nurses']) || !is_array($data['nurses'])
            || !isset($data['links']) || !is_array($data['links'])
            || empty($data['calculationType'])
        ) {
            return new JsonResponse(['status' => false, 'message' => 'Calculation source data invalid'], Response::HTTP_BAD_REQUEST);
        }

        if (LD::checkFeatureFlag(GpCalculator\GpCalculatorService::ESCALATION_GUIDE_FEATURE_FLAG)
            && !$this->hasBranch($data['clients'])
        ) {
            return new JsonResponse(['status' => false, 'message' => 'Branch field is required.'], Response::HTTP_BAD_REQUEST);
        }

        // Run calculations and return the same payload shape, but with computed fields merged in
        $resultPayload = $this->calculator->calculate($data);

        return new JsonResponse(['status'=> true, 'data' => $resultPayload]);
    }

    /**
     * @param array<int,mixed> $clients
     */
    private function hasBranch(array $clients): bool
    {
        foreach ($clients as $client) {
            if (is_array($client) && (int) ($client['branchId'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }
}
