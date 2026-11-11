<?php

namespace App\Jobs\PayerRates;

use App\Helpers\H;
use App\Helpers\Notifications;
use App\Jobs\Job;
use App\Repositories\Candidates\CandidateReadRepository;
use App\Repositories\PayerRate\PayerRateReadRepository;
use Illuminate\Support\Facades\Log;
use Throwable;

class PayerRateExpiringNotificationJob extends Job
{

    const PERMISSION_NAME = 'get_reimbursement_notification';

    public function __construct()
    {
    }

    public function handle(
        PayerRateReadRepository $payerRateReadRepository,
    ): void {
        $notificationText = 'The reimbursement rate for %s will expire on %s. Please review if a new rate is required.';
        $userlist = H::getHelper('UserPermissionResolver')->getUserByPermission(self::PERMISSION_NAME);

        $payerRates = $payerRateReadRepository->getSoonExpiringRecords();

        foreach ($payerRates as $payerRate) {
            foreach ($userlist as $user) {
                $formattedDate = getFormatedDate($payerRate->effectiveThrough);

                $notification = [
                    'related_item' => $payerRate->id,
                    'type' => 'ReimbursementRequest',
                    'title' => 'Notification of Expiring Reimbursement Rate',
                    'notes' => sprintf($notificationText, $payerRate->name, $formattedDate),
                    'user_id' => $user->id,
                ];
                Notifications::addNotification($notification);
            }
        }
    }

    public function failed(Throwable $t): void
    {
        Log::error('PayerRateExpiringNotificationJob', [
            'massage' => $t->getMessage(),
            'exception' => $t,
        ]);
    }
}
