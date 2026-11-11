<?php

namespace App\VBC\Http\Controllers\HospitalizationTrackerDiagnosis;

use App\Http\Controllers\Controller;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Http\Requests\HospitalizationTrackerDiagnosis\StoreHospitalizationTrackerDiagnosisRequest;
use App\VBC\Http\Requests\HospitalizationTrackerDiagnosis\UpdateHospitalizationTrackerDiagnosisRequest;
use App\VBC\Http\Requests\HospitalizationTrackerDiagnosis\UpdateWasDiagnosisMadeRequest;
use App\VBC\Http\Resources\Hospitalization\HospitalizationTrackerResource;
use App\VBC\Http\Resources\HospitalizationTrackerDiagnosis\HospitalizationTrackerDiagnosisResource;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use App\VBC\Repositories\HospitalizationTrackerDiagnosis\HospitalizationTrackerDiagnosisReadRepository;
use App\VBC\Services\HospitalizationTrackerDiagnosis\HospitalizationTrackerDiagnosisNotEditableException;
use App\VBC\Services\HospitalizationTrackerDiagnosis\HospitalizationTrackerDiagnosisService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class HospitalizationTrackerDiagnosisController extends Controller
{
    public function __construct(
        private readonly HospitalizationReadRepository $trackerRepository,
        private readonly HospitalizationTrackerDiagnosisReadRepository $repository,
        private readonly HospitalizationTrackerDiagnosisService $service,
    ) {
    }

    public function store(StoreHospitalizationTrackerDiagnosisRequest $request, int $trackerId): JsonResponse
    {
        $tracker = $this->findTracker($request, $trackerId);

        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $diagnosis = $this->service->create($tracker, $request->toDto());
        } catch (HospitalizationTrackerDiagnosisNotEditableException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return HospitalizationTrackerDiagnosisResource::make($this->repository->findById($diagnosis->id))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateHospitalizationTrackerDiagnosisRequest $request, int $trackerId, int $diagnosisId): JsonResponse
    {
        $tracker = $this->findTracker($request, $trackerId);

        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        $diagnosis = $this->repository->findById($diagnosisId);

        if (empty($diagnosis) || $diagnosis->encounter_id !== $tracker->encounter_id) {
            return response()->json(['message' => 'Diagnosis not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $updated = $this->service->update($tracker, $diagnosis, $request->toDto());
        } catch (HospitalizationTrackerDiagnosisNotEditableException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return HospitalizationTrackerDiagnosisResource::make($updated)->response();
    }

    /**
     * "Was the diagnosis made?" toggle (AC4/AC6) — not part of the diagnoses list, so it returns the
     * whole tracker (like saveField/updateOutcome), not a HospitalizationTrackerDiagnosisResource.
     */
    public function updateWasDiagnosisMade(UpdateWasDiagnosisMadeRequest $request, int $trackerId): JsonResponse
    {
        $tracker = $this->findTracker($request, $trackerId);

        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->service->setWasDiagnosisMade($tracker, $request->value());
        } catch (HospitalizationTrackerDiagnosisNotEditableException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return HospitalizationTrackerResource::make($this->trackerRepository->findForForm($trackerId))->response();
    }

    /**
     * Diagnosis serves both the Hospitalization (IP) and ED Visit viewers, and a tracker id alone doesn't say
     * which one is asking. `encounter_type` (optional, IP when omitted — the Hospitalization viewer) states
     * the expected kind; a tracker of the other kind 404s like an unknown id. Same rule as
     * HospitalizationTrackerTrainingController::findTracker().
     */
    private function findTracker(
        StoreHospitalizationTrackerDiagnosisRequest|
        UpdateHospitalizationTrackerDiagnosisRequest|
        UpdateWasDiagnosisMadeRequest $request,
        int $trackerId,
    ): ?HospitalizationTrackerModel {
        $tracker = $this->trackerRepository->findForForm($trackerId);
        $encounterType = $request->enum('encounter_type', EncounterTypeEnum::class) ?? EncounterTypeEnum::IP;

        return $tracker?->encounter->type === $encounterType ? $tracker : null;
    }
}
