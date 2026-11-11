<?php

namespace App\VBC\Services\Hospitalization;

use App\VBC\DTO\Hospitalization\HospitalizationTrackerFieldsDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use App\VBC\Repositories\Hospitalization\HospitalizationWriteRepository;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The one path every edit of a tracker's form fields takes, whichever endpoint it arrived through: the
 * header form's single-field save and the Current Hospitalization Info section's whole-section submit.
 * Keeping them together is what guarantees a field is stored the same way and logged the same way
 * regardless of where it was edited.
 */
class HospitalizationTrackerUpdateService
{
    public function __construct(
        private readonly HospitalizationReadRepository $readRepository,
        private readonly HospitalizationWriteRepository $writeRepository,
        private readonly HospitalizationTrackerFieldValues $fieldValues,
        private readonly HospitalizationTrackerActivityLog $activityLog,
    ) {
    }

    /**
     * Writes the submitted fields and returns the tracker as it now stands. The Activity Log entries are
     * built by comparing the values read back afterwards with the ones read before — not with what was
     * submitted — so an entry can only exist for a change that really landed.
     *
     * @throws Throwable
     */
    public function updateFields(
        HospitalizationTrackerModel $tracker,
        HospitalizationTrackerFieldsDTO $fields
    ): ?HospitalizationTrackerModel {
        $submitted = array_map(
            fn (string $key) => HospitalizationTrackerFieldEnum::from($key),
            array_keys($fields->values)
        );

        $before = $this->fieldValues->readAll($tracker, $submitted);

        DB::transaction(fn () => $this->writeRepository->updateFields($tracker, $fields));

        $updated = $this->readRepository->findForForm($tracker->id);
        if (empty($updated)) {
            return null;
        }

        $this->activityLog->recordChanges($tracker, $before, $this->fieldValues->readAll($updated, $submitted));

        return $updated;
    }
}
