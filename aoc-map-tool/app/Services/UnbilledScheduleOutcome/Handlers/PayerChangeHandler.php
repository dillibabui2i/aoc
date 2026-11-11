<?php

namespace App\Services\UnbilledScheduleOutcome\Handlers;

use App\User;

class PayerChangeHandler extends Handler
{
    /**
     * @param string $notes
     * @return bool
     */
    public function supports(string $notes): bool
    {
        foreach (self::CHANGE_NOTES as $changeNote) {
            if (str_contains(strtolower($notes), strtolower($changeNote))) {
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
            self::PAYER_CHANGE_REASON,
            $datum['notes']
        );

        $this->addActivityNotes($ids, $datum, self::PAYER_CHANGE_REASON);
        $this->scheduleMasterModel->whereIn('id', $ids)->update([
            'billingReason' => self::PAYER_CHANGE_REASON,
            'billingReasonAddedAt' => now(),
            'billingReasonAddedBy' => $user->id,
            'billingNotes' => $reason,
            'billingComments' => $datum['notes'],
        ]);
    }
}
