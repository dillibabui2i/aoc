<?php

namespace App\Services\UnbilledScheduleOutcome\Handlers;

use App\User;

class ServiceCorrectionHandler extends Handler
{
    /**
     * @param string $notes
     * @return bool
     */
    public function supports(string $notes): bool
    {
        foreach (self::CORRECTION_NOTES as $correctionNote) {
            if (str_contains(strtolower($notes), strtolower($correctionNote))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $ids
     * @param array $datum
     * @param User $user
     * @return void
     */
    public function handle(array $ids, array $datum, User $user): void
    {
        $reason = $this->formatBillingNote(
            $user->name,
            $this->returnFormatedDate(now()),
            self::SERVICE_CORRECTION_REASON,
            $datum['notes']
        );

        $this->addActivityNotes($ids, $datum, self::SERVICE_CORRECTION_REASON);
        $this->scheduleMasterModel->whereIn('id', $ids)->update([
            'billingReason' => self::SERVICE_CORRECTION_REASON,
            'billingSubReason' => self::SERVICE_CORRECTION_SUB_REASON,
            'billingReasonAddedAt' => now(),
            'billingReasonAddedBy' => $user->id,
            'billingNotes' => $reason,
            'billingComments' => $datum['notes'],
        ]);
    }
}
