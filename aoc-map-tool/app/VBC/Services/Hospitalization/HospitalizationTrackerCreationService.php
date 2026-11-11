<?php

namespace App\VBC\Services\Hospitalization;

use App\VBC\DTO\Hospitalization\HospitalizationTrackerCreationDTO;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use App\VBC\Repositories\Hospitalization\HospitalizationWriteRepository;
use App\VBC\Services\SickPlan\SickPlanService;
use Illuminate\Support\Facades\DB;
use Throwable;

class HospitalizationTrackerCreationService
{
    /**
     * Two simultaneous submits for the same client both gap-lock the client's (empty) encounter range and
     * then both try to insert into that gap, which InnoDB resolves by killing one with a deadlock. The
     * retry is what turns that loser into a normal conflict: second time round the winner has committed,
     * so the duplicate check simply finds the new tracker and the caller gets a 409 instead of a 500.
     */
    private const CREATE_ATTEMPTS = 3;

    public function __construct(
        private readonly HospitalizationReadRepository $readRepository,
        private readonly HospitalizationWriteRepository $writeRepository,
        private readonly SickPlanService $sickPlanService,
    ) {
    }

    /**
     * Opens a hospitalization tracker for a client: encounter first (the tracker hangs off it), then the
     * tracker itself, then the header fields the user filled in. All in one transaction so a failure
     * can't leave an encounter with no tracker, or a tracker missing what was submitted with it.
     *
     * Returns null when the client already has an active tracker — the caller turns that into the
     * conflict response the UI needs to offer "open the active tracker" / "search another client".
     * @throws Throwable
     */
    public function create(HospitalizationTrackerCreationDTO $dto): ?HospitalizationTrackerModel
    {
        $trackerId = DB::transaction(function () use ($dto) {
            // The duplicate check has to sit inside the lock: the client may have gained a tracker
            // between the UI's up-front check and this submit, possibly from another user submitting at
            // the same moment, and checking before the transaction would let both requests through.
            $this->writeRepository->lockEncountersForPatient($dto->patientId);

            // Any active tracker (IP or ED) blocks both an ED Visit (PD-3983 AC5/AC18) and a Hospitalization.
            if (!empty($this->readRepository->findActiveTrackerForPatient($dto->patientId))) {
                return null;
            }

            $encounter = $this->writeRepository->createEncounterForPatient($dto->patientId, $dto->encounterType);
            $tracker = $this->writeRepository->createTracker($encounter->id, (int) auth()->id(), $dto->initialStatus);

            // Whatever the user set on the form before submitting is stored through the same path as a
            // later edit, so creation and editing can't diverge on where a field lives.
            $this->writeRepository->updateFields($tracker, $dto->fields);

            // A tracker's status is only ever "Open" (Hospitalization) or "In Progress" (ED Visit,
            // PD-3983) for the first time here (AC3/AC7): the client's Sick Plan is created blank if
            // this is their first hospitalization/ED visit, or reused as-is otherwise (AC5/AC12) —
            // never duplicated, and shared across both tracker kinds since it belongs to the client.
            $this->sickPlanService->ensureForPatient($dto->patientId, (int) auth()->id());

            return $tracker->id;
        }, self::CREATE_ATTEMPTS);

        return empty($trackerId) ? null : $this->readRepository->findForForm($trackerId);
    }
}
