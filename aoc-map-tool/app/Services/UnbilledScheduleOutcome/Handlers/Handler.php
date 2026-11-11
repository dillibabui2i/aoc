<?php

namespace App\Services\UnbilledScheduleOutcome\Handlers;

use App\Helpers\ActivityNotes;
use App\Helpers\LD;
use App\ScheduleMaster;
use App\User;
use Carbon\Carbon;

abstract class Handler
{
    protected const PAYER_CHANGE_REASON = 'Payer Change';
    protected const SERVICE_CORRECTION_REASON = 'Service Correction';
    protected const SERVICE_CORRECTION_SUB_REASON = 'Other';
    protected const CORRECTION_NOTES = ['Thoughtful AI: Primary Dx', 'Thoughtful AI: CPT/Modifier'];
    protected const CHANGE_NOTES = [
        'Thoughtful AI: Payer is incorrect in Kantime',
        'Corresponding client was not found in NetSmart under this payer'
    ];
    private const BILLING_NOTE_PATTERN = '%s %s commented %s Notes: %s';
    private const ACTIVITY_NOTE_PATTERN = '%s Notes: %s';

    protected ScheduleMaster $scheduleMasterModel;
    protected ActivityNotes $activityNotesHelper;

    /**
     * @param ScheduleMaster $scheduleMasterModel
     * @param ActivityNotes $activityNotesHelper
     */
    public function __construct(ScheduleMaster $scheduleMasterModel, ActivityNotes $activityNotesHelper)
    {
        $this->scheduleMasterModel = $scheduleMasterModel;
        $this->activityNotesHelper = $activityNotesHelper;
    }

    /**
     * @param string $userName
     * @param string $data
     * @param string $reason
     * @param string $notes
     * @return string
     */
    public function formatBillingNote(string $userName, string $data, string $reason, string $notes): string
    {
        return sprintf(self::BILLING_NOTE_PATTERN, $data, $userName, $reason, $notes);
    }

    /**
     * @param array $ids
     * @param array $data
     * @param string $reason
     * @return void
     */
    public function addActivityNotes(array $ids, array $data, string $reason): void
    {
        if (empty($ids)) {
            return;
        }

        foreach ($ids as $id) {
            $logData = [
                'entity' => 'billing',
                'notes' => sprintf(self::ACTIVITY_NOTE_PATTERN, $reason, $data['notes']),
                'entity_id' => $id,
                'misc4' => time()
            ];
            $this->activityNotesHelper->setActivityNotesFromConsole($logData);
        }
    }

    /**
     * @param string $date
     * @return string
     */
    public function returnFormatedDate(string $date): string
    {
        return Carbon::parse($date)->format('m/d');
    }

    /**
     * @param string $notes
     * @return bool
     */
    abstract public function supports(string $notes): bool;

    /**
     * @param array $ids
     * @param array $datum
     * @param User $user
     * @return void
     */
    abstract public function handle(array $ids, array $datum, User $user): void;
}
