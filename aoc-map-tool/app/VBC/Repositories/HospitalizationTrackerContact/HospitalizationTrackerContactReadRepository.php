<?php

namespace App\VBC\Repositories\HospitalizationTrackerContact;

use App\VBC\Models\HospitalizationTrackerContact\HospitalizationTrackerContactModel;
use App\VBC\Models\PatientContact\ContactTypeModel;
use Illuminate\Database\Eloquent\Collection;

class HospitalizationTrackerContactReadRepository
{
    public function __construct(
        private HospitalizationTrackerContactModel $contactModel,
        private ContactTypeModel $contactTypeModel,
    ) {
    }

    public function findById(int $id): ?HospitalizationTrackerContactModel
    {
        /** @var HospitalizationTrackerContactModel|null $contact */
        $contact = $this->contactModel->newQuery()->with(['type', 'receivedBy'])->find($id);

        return $contact;
    }

    /** Active (non-archived) options for the "Relationship" dropdown. */
    public function getActiveTypeOptions(): Collection
    {
        return $this->contactTypeModel->newQuery()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
