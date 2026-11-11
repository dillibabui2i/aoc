<?php

namespace App\VBC\Http\Controllers\SickPlan;

use App\Http\Controllers\Controller;
use App\VBC\Http\Requests\SickPlan\UpdateSickPlanItemRequest;
use App\VBC\Http\Resources\SickPlan\SickPlanResource;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use App\VBC\Repositories\SickPlan\SickPlanReadRepository;
use App\VBC\Services\SickPlan\SickPlanNotEditableException;
use App\VBC\Services\SickPlan\SickPlanService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SickPlanController extends Controller
{
    public function __construct(
        private readonly HospitalizationReadRepository $trackerRepository,
        private readonly SickPlanReadRepository $sickPlanRepository,
        private readonly SickPlanService $sickPlanService,
    ) {
    }

    /** The client's Sick Plan, resolved through the tracker (AC4/AC6/AC11): one per client, shared across trackers. */
    public function show(int $trackerId): JsonResponse
    {
        $tracker = $this->trackerRepository->findForForm($trackerId);
        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        $plan = $this->sickPlanRepository->findForPatient($tracker->encounter->patient_id);
        if (empty($plan)) {
            return response()->json(['message' => 'Sick Plan not found.'], Response::HTTP_NOT_FOUND);
        }

        return SickPlanResource::make($plan)->response();
    }

    /**
     * @throws Throwable
     */
    public function update(UpdateSickPlanItemRequest $request, int $trackerId, int $itemId): JsonResponse
    {
        $tracker = $this->trackerRepository->findForForm($trackerId);
        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $plan = $this->sickPlanService->updateItem($tracker, $itemId, $request->toDto());
        } catch (SickPlanNotEditableException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return SickPlanResource::make($plan)->response();
    }
}
