<?php

namespace App\VBC\Http\Controllers\HospitalizationTrackerContact;

use App\Http\Controllers\Controller;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Http\Requests\HospitalizationTrackerContact\StoreHospitalizationTrackerContactRequest;
use App\VBC\Http\Requests\HospitalizationTrackerContact\UpdateHospitalizationTrackerContactRequest;
use App\VBC\Http\Resources\HospitalizationTrackerContact\HospitalizationTrackerContactResource;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use App\VBC\Repositories\HospitalizationTrackerContact\HospitalizationTrackerContactReadRepository;
use App\VBC\Services\HospitalizationTrackerContact\HospitalizationTrackerContactNotEditableException;
use App\VBC\Services\HospitalizationTrackerContact\HospitalizationTrackerContactService;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class HospitalizationTrackerContactController extends Controller
{
    public function __construct(
        private readonly HospitalizationReadRepository $trackerRepository,
        private readonly HospitalizationTrackerContactReadRepository $repository,
        private readonly HospitalizationTrackerContactService $service,
    ) {
    }

    public function store(StoreHospitalizationTrackerContactRequest $request, int $trackerId): JsonResponse
    {
        $tracker = $this->findTracker($request, $trackerId);

        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $contact = $this->service->create($tracker, $request->toDto());
        } catch (HospitalizationTrackerContactNotEditableException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return HospitalizationTrackerContactResource::make($this->repository->findById($contact->id))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateHospitalizationTrackerContactRequest $request, int $trackerId, int $contactId): JsonResponse
    {
        $tracker = $this->findTracker($request, $trackerId);

        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        $contact = $this->repository->findById($contactId);

        if (empty($contact) || $contact->hospitalization_tracker_id !== $tracker->id) {
            return response()->json(['message' => 'Contact not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $updated = $this->service->update($tracker, $contact, $request->toDto());
        } catch (HospitalizationTrackerContactNotEditableException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return HospitalizationTrackerContactResource::make($updated)->response();
    }

    /** Options for the "Relationship" dropdown. */
    public function getTypes(): JsonResponse
    {
        return response()->json(
            $this->repository->getActiveTypeOptions()->map(fn ($type) => [
                'value' => $type->id,
                'label' => $type->name,
            ])
        );
    }

    /**
     * Contacts serve both the Hospitalization (IP) and ED Visit viewers, and a tracker id alone doesn't say
     * which one is asking. `encounter_type` (optional, IP when omitted — the Hospitalization viewer) states
     * the expected kind; a tracker of the other kind 404s like an unknown id, same as `showTracker()`.
     */
    private function findTracker(StoreHospitalizationTrackerContactRequest|UpdateHospitalizationTrackerContactRequest $request, int $trackerId): ?HospitalizationTrackerModel
    {
        $tracker = $this->trackerRepository->findForForm($trackerId);
        $encounterType = $request->enum('encounter_type', EncounterTypeEnum::class) ?? EncounterTypeEnum::IP;

        return $tracker?->encounter->type === $encounterType ? $tracker : null;
    }
}
