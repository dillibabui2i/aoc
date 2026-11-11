<?php

namespace App\Services\Dashboard\Buckets\Billing\UnbilledSchedules;

use App\Enums\MssqlScheduleMasterType;
use App\ScheduleMaster;
use App\Helpers\H;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class UnbilledSchedulesService
{
    private const ERROR_BILLING_EXCLUDE = 'billingExclude() - rollback: %s | trace: %s';

    public function billingExclude(array $ids, string $type): bool
    {
        DB::beginTransaction();
        try {
            $userName = Auth::getUser()->getAttributes()['name'];

            if ($type === MssqlScheduleMasterType::BILL_READY->value) {
                $date = Carbon::now()->format('m/d');

                DB::table('mssql_schedulemaster')
                    ->whereIn('id', $ids)
                    ->update([
                        'is_deleted' => 1,
                        'billingNotes' => DB::raw("CONCAT('$date $userName manually excluded this record\n',
                        COALESCE(billingNotes, ''))")
                    ]);
            } elseif ($type === MssqlScheduleMasterType::NOT_BILL_READY->value) {
                DB::table('mssql_schedulemaster')->whereIn('id', $ids)->update(['is_deleted' => 1]);

                if (count($ids) > 0) {
                    foreach ($ids as $id) {
                        $logData = [
                            'entity' => 'billing',
                            'type' => $type,
                            'notes' => $userName . ' manually excluded this record',
                            'entity_id' => $id,
                            'misc4' => time()
                        ];
                        H::getHelper("ActivityNotes")->setActivityNotesFromConsole($logData);
                    }
                }
            }

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            Log::error(
                sprintf(
                    self::ERROR_BILLING_EXCLUDE,
                    $e->getMessage(),
                    $e->getTraceAsString()
                )
            );

            return false;
        }
    }
}
