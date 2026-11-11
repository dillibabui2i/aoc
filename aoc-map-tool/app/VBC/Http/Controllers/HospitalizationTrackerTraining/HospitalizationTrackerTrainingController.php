<?php

namespace App\VBC\Http\Controllers\HospitalizationTrackerTraining;

use App\Http\Controllers\Controller;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Http\Requests\HospitalizationTrackerTraining\StoreHospitalizationTrackerTrainingRequest;
use App\VBC\Http\Requests\HospitalizationTrackerTraining\UpdateHospitalizationTrackerTrainingRequest;
use App\VBC\Http\Requests\HospitalizationTrackerTraining\UpdateStaffFamilyTrainingRequiredRequest;
use App\VBC\Http\Resources\Hospitalization\HospitalizationTrackerResource;
use App\VBC\Http\Resources\HospitalizationTrackerTraining\HospitalizationTrackerTrainingResource;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use App\VBC\Repositories\HospitalizationTrackerTraining\HospitalizationTrackerTrainingReadRepository;
use App\VBC\Services\HospitalizationTrackerTraining\HospitalizationTrackerTrainingNotEditableException;
use App\VBC\Services\HospitalizationTrackerTraining\HospitalizationTrackerTrainingService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class HospitalizationTrackerTrainingController extends Controller
{
    public function __construct(
        private readonly HospitalizationReadRepository $trackerRepository,
        private readonly HospitalizationTrackerTrainingReadRepository $repository,
        private readonly HospitalizationTrackerTrainingService $service,
    ) {
    }

    public function store(StoreHospitalizationTrackerTrainingRequest $request, int $trackerId): JsonResponse
    {
        $tracker = $this->findTracker($request, $trackerId);

        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $training = $this->service->create($tracker, $request->toDto());
        } catch (HospitalizationTrackerTrainingNotEditableException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return HospitalizationTrackerTrainingResource::make($this->repository->findById($training->id))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateHospitalizationTrackerTrainingRequest $request, int $trackerId, int $educationId): JsonResponse
    {
        $tracker = $this->findTracker($request, $trackerId);

        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        $training = $this->repository->findById($educationId);

        if (empty($training) || $training->hospitalization_tracker_id !== $tracker->id) {
            return response()->json(['message' => 'Training/Education record not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $updated = $this->service->update($tracker, $training, $request->toDto());
        } catch (HospitalizationTrackerTrainingNotEditableException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return HospitalizationTrackerTrainingResource::make($updated)->response();
    }

    /**
     * "Staff / Family Training/Education required?" toggle — not part of the Education records list,
     * so it returns the whole tracker (like updateWasDiagnosisMade), not a
     * HospitalizationTrackerTrainingResource.
     */
    public function updateStaffFamilyTrainingRequired(UpdateStaffFamilyTrainingRequiredRequest $request, int $trackerId): JsonResponse
    {
        $tracker = $this->findTracker($request, $trackerId);

        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->service->setStaffFamilyTrainingRequired($tracker, $request->value());
        } catch (HospitalizationTrackerTrainingNotEditableException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return HospitalizationTrackerResource::make($this->trackerRepository->findForForm($trackerId))->response();
    }

    /** Options for the "Training Type" dropdown. */
    public function getTypes(): JsonResponse
    {
        return response()->json(
            $this->repository->getActiveTypeOptions()->map(fn ($type) => [
                'value' => $type->id,
                'label' => $type->name,
            ])
        );
    }

    /** Options for the "Education Source" dropdown. */
    public function getSources(): JsonResponse
    {
        return response()->json(
            $this->repository->getActiveSourceOptions()->map(fn ($source) => [
                'value' => $source->id,
                'label' => $source->name,
            ])
        );
    }

    /**
     * Training/Education serves both the Hospitalization (IP) and ED Visit viewers, and a tracker id alone
     * doesn't say which one is asking. `encounter_type` (optional, IP when omitted — the Hospitalization
     * viewer) states the expected kind; a tracker of the other kind 404s like an unknown id. Same rule as
     * HospitalizationTrackerContactController::findTracker().
     */
    private function findTracker(
        StoreHospitalizationTrackerTrainingRequest|UpdateHospitalizationTrackerTrainingRequest|UpdateStaffFamilyTrainingRequiredRequest $request,
        int $trackerId,
    ): ?HospitalizationTrackerModel {
        $tracker = $this->trackerRepository->findForForm($trackerId);
        $encounterType = $request->enum('encounter_type', EncounterTypeEnum::class) ?? EncounterTypeEnum::IP;

        return $tracker?->encounter->type === $encounterType ? $tracker : null;
    }
}
