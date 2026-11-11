<?php

namespace App\VBC\Http\Controllers\Hospitalization;

use App\Http\Controllers\Controller;
use App\VBC\DTO\Hospitalization\HospitalizationTrackerFieldsDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Http\Requests\Followup\UpdateEdVisitFollowupRequest;
use App\VBC\Http\Requests\Hospitalization\CreateEdVisitTrackerRequest;
use App\VBC\Http\Requests\Hospitalization\CreateHospitalizationTrackerRequest;
use App\VBC\Http\Requests\Hospitalization\GetHospitalizationAssigneesRequest;
use App\VBC\Http\Requests\Hospitalization\GetHospitalizationsRequest;
use App\VBC\Http\Requests\Hospitalization\SearchHospitalizationClientsRequest;
use App\VBC\Http\Requests\Hospitalization\ShowHospitalizationTrackerRequest;
use App\VBC\Http\Requests\Hospitalization\StoreHospitalizationTrackerNoteRequest;
use App\VBC\Http\Requests\Hospitalization\UpdateCurrentHospitalizationInfoRequest;
use App\VBC\Http\Requests\Hospitalization\UpdateEdVisitInfoRequest;
use App\VBC\Http\Requests\Hospitalization\UpdateHospitalizationTrackerFieldRequest;
use App\VBC\Http\Requests\Hospitalization\UpdateHospitalizationTrackerOutcomeRequest;
use App\VBC\Http\Resources\Hospitalization\AllHospitalizationCollection;
use App\VBC\Http\Resources\Hospitalization\EdVisitCollection;
use App\VBC\Http\Resources\Hospitalization\HospitalizationClientProfileResource;
use App\VBC\Http\Resources\Hospitalization\HospitalizationClientResource;
use App\VBC\Http\Resources\Hospitalization\HospitalizationCollection;
use App\VBC\Http\Resources\Hospitalization\HospitalizationTrackerHistoryResource;
use App\VBC\Http\Resources\Hospitalization\HospitalizationTrackerNoteResource;
use App\VBC\Http\Resources\Hospitalization\HospitalizationTrackerResource;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use App\VBC\Services\Followup\EdVisitFollowupService;
use App\VBC\Services\Followup\InvalidFollowupOutcomeTransitionException;
use App\VBC\Services\Hospitalization\HospitalizationExportService;
use App\VBC\Services\Hospitalization\HospitalizationTrackerActivityLog;
use App\VBC\Services\Hospitalization\HospitalizationTrackerCreationService;
use App\VBC\Services\Hospitalization\HospitalizationTrackerOutcomeService;
use App\VBC\Services\Hospitalization\HospitalizationTrackerUpdateService;
use App\VBC\Services\Hospitalization\InvalidHospitalizationOutcomeTransitionException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class HospitalizationController extends Controller
{
    public function __construct(
        private readonly HospitalizationReadRepository $repository,
        private readonly HospitalizationTrackerCreationService $creationService,
        private readonly HospitalizationTrackerUpdateService $updateService,
        private readonly HospitalizationTrackerOutcomeService $outcomeService,
        private readonly HospitalizationTrackerActivityLog $activityLog,
        private readonly HospitalizationExportService $exportService,
        private readonly EdVisitFollowupService $edVisitFollowupService,
    ) {
    }

    public function index(): Renderable
    {
        return view('vbc.hospitalisation.index');
    }

    /** Bucket widget on the ICM dashboard; not yet wired to a real frontend. Scoped to active trackers only. */
    public function getHospitalizations(GetHospitalizationsRequest $request): HospitalizationCollection
    {
        $dto = $request->toDto();
        $paginated = $this->repository->getBucketPaginated($dto);

        return new HospitalizationCollection($paginated);
    }

    /** Triage "ED Visits" bucket widget (PD-4057). In Progress, Ready for Review and Ready for Next Steps trackers only; encounter type comes from the request. */
    public function getEdVisits(GetHospitalizationsRequest $request): EdVisitCollection
    {
        return new EdVisitCollection($this->repository->getEdVisitsBucketPaginated($request->toDto()));
    }

    public function getAllHospitalizations(GetHospitalizationsRequest $request): AllHospitalizationCollection
    {
        $dto = $request->toDto();
        $paginated = $this->repository->getPaginated($dto);

        return new AllHospitalizationCollection($paginated);
    }

    public function exportAllHospitalizations(GetHospitalizationsRequest $request): StreamedResponse
    {
        return $this->exportService->export($request->toDto());
    }

    /** Autocomplete for the "Client Name" field on the Hospitalization Record form. */
    public function searchClients(SearchHospitalizationClientsRequest $request): ResourceCollection
    {
        return HospitalizationClientResource::collection($this->repository->searchClients($request->toDto()));
    }

    /**
     * Everything the form needs the moment a client is picked: the values to pre-fill, plus whether the
     * client already has a tracker in flight so the UI can offer to open that one instead of creating a
     * duplicate. One call rather than two, because the form needs both at the same moment.
     */
    public function showClient(int $patientId): JsonResponse
    {
        $patient = $this->repository->findClient($patientId);

        if (empty($patient)) {
            return response()->json(['message' => 'Client not found.'], Response::HTTP_NOT_FOUND);
        }

        $activeTracker = $this->repository->findActiveTrackerForPatient($patientId);

        return HospitalizationClientProfileResource::make($patient)
            ->additional([
                'meta' => [
                    'has_active_tracker' => !empty($activeTracker),
                    'active_tracker_id' => $activeTracker?->id,
                    'active_tracker_encounter_type' => $activeTracker?->encounter?->type?->value,
                ],
            ])
            ->response();
    }

    /**
     * Options for the "Assigned To" dropdown: users holding the Intensive Case Management permission,
     * or the "Manage ED Visits" one for encounter_type=ED.
     */
    public function getAssignees(GetHospitalizationAssigneesRequest $request): JsonResponse
    {
        $assignees = $request->enum('encounter_type', EncounterTypeEnum::class) === EncounterTypeEnum::ED
            ? $this->repository->getEdAssigneeOptions()
            : $this->repository->getIcmAssigneeOptions();

        return response()->json(
            $assignees->map(fn ($user) => [
                'value' => $user->id,
                'label' => $user->name,
            ])
        );
    }

    /**
     * @throws Throwable
     */
    public function storeTracker(CreateHospitalizationTrackerRequest $request): JsonResponse
    {
        $dto = $request->toDto();
        $tracker = $this->creationService->create($dto);

        if (empty($tracker)) {
            $active = $this->repository->findActiveTrackerForPatient($dto->patientId);

            return response()->json([
                'message' => 'This client already has an active hospitalization tracker.',
                'tracker_id' => $active?->id,
                'encounter_type' => $active?->encounter->type->value,
            ], Response::HTTP_CONFLICT);
        }

        return HospitalizationTrackerResource::make($tracker)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * "ED Visit Record" form submit (PD-3983): same create path as `storeTracker()`, except the tracker
     * produced is an ED encounter saved straight to "In Progress" (AC7) — see CreateEdVisitTrackerRequest.
     * Still a 409 against any active tracker of any kind (AC5/AC18), since the lookup here is made with no
     * encounter type filter (null = any).
     *
     * @throws Throwable
     */
    public function storeEdVisitTracker(CreateEdVisitTrackerRequest $request): JsonResponse
    {
        $dto = $request->toDto();
        $tracker = $this->creationService->create($dto);

        if (empty($tracker)) {
            $active = $this->repository->findActiveTrackerForPatient($dto->patientId);

            return response()->json([
                'message' => 'This client already has an active hospitalization tracker.',
                'tracker_id' => $active?->id,
                'encounter_type' => $active?->encounter?->type?->value,
            ], Response::HTTP_CONFLICT);
        }

        return HospitalizationTrackerResource::make($tracker)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * `encounter_type` (IP/ED) is required so a tracker id of one kind can't be opened through the
     * other kind's viewer (PD-3983 follow-up) — a mismatch 404s the same as an unknown id, rather than
     * leaking which encounter type the id actually belongs to.
     */
    public function showTracker(ShowHospitalizationTrackerRequest $request, int $trackerId): JsonResponse
    {
        $tracker = $this->repository->findForForm($trackerId);

        if (empty($tracker) || $tracker->encounter->type !== $request->toDto()->encounterType) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        return HospitalizationTrackerResource::make($tracker)->response();
    }

    /** One field of the header form, saved as soon as it changes. */
    public function updateTracker(UpdateHospitalizationTrackerFieldRequest $request, int $trackerId): JsonResponse
    {
        return $this->applyFields($trackerId, $request->toDto());
    }

    /**
     * The Current Hospitalization Info section, saved as a whole: its fields are validated against each
     * other's required rules, so there is no per-field save for them.
     */
    public function updateCurrentInfo(UpdateCurrentHospitalizationInfoRequest $request, int $trackerId): JsonResponse
    {
        return $this->applyFields($trackerId, $request->toDto());
    }

    /**
     * The "ED Visit Record" form's "Hospitalization Info" tab (PD-3983), saved as a whole — the ED
     * counterpart of updateCurrentInfo() above, against its own field set
     * (HospitalizationTrackerFieldEnum::edVisitInfoFields()).
     */
    public function updateEdVisitInfo(UpdateEdVisitInfoRequest $request, int $trackerId): JsonResponse
    {
        return $this->applyFields($trackerId, $request->toDto());
    }

    /** Outcome status transition (Open -> In Progress -> Discharged -> Ready for Review -> Ready for Next Steps -> Completed, or -> Closed). */
    public function updateOutcome(UpdateHospitalizationTrackerOutcomeRequest $request, int $trackerId): JsonResponse
    {
        $tracker = $this->repository->findForForm($trackerId);

        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $updated = $this->outcomeService->transition($tracker, $request->toDto());
        } catch (InvalidHospitalizationOutcomeTransitionException $e) {
            return response()->json(
                array_filter(['message' => $e->getMessage(), 'missing_fields' => $e->missingFields]),
                Response::HTTP_CONFLICT
            );
        }

        return HospitalizationTrackerResource::make($updated)->response();
    }

    /** ED Visit follow-up outcome (PD-4073): First/Second/Third Attempt or Completed, notes required. */
    public function updateEdVisitFollowup(UpdateEdVisitFollowupRequest $request, int $trackerId): JsonResponse
    {
        $tracker = $this->repository->findForForm($trackerId);

        if (empty($tracker) || $tracker->encounter->type !== EncounterTypeEnum::ED) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $updated = $this->edVisitFollowupService->transition($tracker, $request->toDto());
        } catch (InvalidFollowupOutcomeTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return HospitalizationTrackerResource::make($updated)->response();
    }

    /** The tracker's Activity Log — the shared `notes` rows this module writes on every logged change. */
    public function getTrackerNotes(int $trackerId): JsonResponse
    {
        $tracker = $this->repository->findForForm($trackerId);

        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        return HospitalizationTrackerNoteResource::collection($this->activityLog->notesFor($trackerId))->response();
    }

    /** "Hospitalization History" tab: the hospitalizations this one was transferred from, nearest first. */
    public function getTrackerHistory(int $trackerId): JsonResponse
    {
        $tracker = $this->repository->findForForm($trackerId);

        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        return HospitalizationTrackerHistoryResource::collection($this->repository->findTransferHistory($tracker))->response();
    }

    /** "Create Notes" modal submission (AC5-AC10), blocked once the tracker is Closed or Completed (AC11/AC12). */
    public function storeTrackerNote(StoreHospitalizationTrackerNoteRequest $request, int $trackerId): JsonResponse
    {
        $tracker = $this->repository->findForForm($trackerId);

        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        // ED Visits (PD-3972) are blocked once Completed, Closed or Cancelled;
        // Hospitalizations once Closed or Completed.
        $blockedStatuses = $tracker->encounter->type === EncounterTypeEnum::ED
            ? [
                HospitalizationTrackerStatusEnum::COMPLETED,
                HospitalizationTrackerStatusEnum::CLOSED,
                HospitalizationTrackerStatusEnum::CANCELLED,
            ]
            : [HospitalizationTrackerStatusEnum::CLOSED, HospitalizationTrackerStatusEnum::COMPLETED];

        if (in_array($tracker->status, $blockedStatuses, true)) {
            return response()->json([
                'message' => 'Notes cannot be added to a closed or completed hospitalization tracker.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->activityLog->write($tracker, $request->toDto()->notes);

        return HospitalizationTrackerNoteResource::collection($this->activityLog->notesFor($trackerId))->response();
    }

    private function applyFields(int $trackerId, HospitalizationTrackerFieldsDTO $fields): JsonResponse
    {
        $tracker = $this->repository->findForForm($trackerId);

        if (empty($tracker)) {
            return response()->json(['message' => 'Hospitalization tracker not found.'], Response::HTTP_NOT_FOUND);
        }

        $updated = $this->updateService->updateFields($tracker, $fields);

        return HospitalizationTrackerResource::make($updated)->response();
    }
}
