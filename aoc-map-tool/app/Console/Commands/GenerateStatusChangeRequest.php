<?php

namespace App\Console\Commands;

use App\Enums\MssqlEmploymentStatus;
use App\Enums\MssqlScheduleMasterStatus;
use App\Enums\RolesEnum;
use App\Enums\UserStatus;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\MssqlProviders;
use Carbon\Carbon;

class GenerateStatusChangeRequest extends Command
{
    private const REQUESTED_OUTCOME = 'Requested';
    private const COMPLETED_OUTCOME = 'Completed';

    protected $signature = 'statuschangerequest:generate';

    protected $description = 'Automated process to create status change requests';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $chunkSize = 100;
        $finalQuery = $this->getProvidersNeedingCoverage();

        $finalQuery->orderBy('id')->chunk($chunkSize, function ($providers) {
            $date = Carbon::now();
            foreach ($providers as $provider) {
                if ($provider->request_outcome == self::COMPLETED_OUTCOME &&
                    $provider->request_last_followup > date(
                        'Y-m-d',
                        strtotime(date('Y-m-d') . ' - 4 days'),
                    )) {
                    continue;
                }

                $updateData = [];
                $updateData['requested_status'] = MssqlEmploymentStatus::TERMINATED->value;
                $updateData['request_outcome'] = self::REQUESTED_OUTCOME;
                $updateData['request_reason'] = 'No Shift Activity 365+ Days';
                $updateData['request_date'] = $date->format('Y-m-d');
                $updateData['request_by'] = User::SYSTEM_ID;
                $updateData['scr_original_status_id'] = $provider->EmploymentStatusID;

                //Get OC
                if (!empty($ocData)) {
                    $updateData['request_oc'] = $ocData->oc;
                }
                MssqlProviders::where('id', $provider->id)->update($updateData);
            }
        });
        return 0;
    }

    public function getProvidersNeedingCoverage()
    {
        $year = date('Y');
        $daysInYear = date('z', strtotime("$year-12-31"));
        $employmentStatuses = [
            MssqlEmploymentStatus::ACTIVE->value,
            MssqlEmploymentStatus::ACTIVE_COVERAGE->value,
            MssqlEmploymentStatus::STANDBY->value,
        ];

        $scheduleStatuses = [
            MssqlScheduleMasterStatus::APPROVED->value,
            MssqlScheduleMasterStatus::COMPLETED->value,
            MssqlScheduleMasterStatus::IN_PROGRESS->value,
            MssqlScheduleMasterStatus::PLANNED->value,
        ];

        $hireDateExpr = 'DATE(IF(
        mp.RehireDate = "0000-00-00" OR mp.RehireDate = "1900-01-01",
        mp.HiredDate,
        COALESCE(mp.RehireDate, mp.HiredDate)
    ))';

        $diffExpr = "CASE
        WHEN ls.ScheduleDate IS NOT NULL
            THEN DATEDIFF(NOW(), ls.ScheduleDate)
        ELSE DATEDIFF(NOW(), {$hireDateExpr})
    END";

        $latestSchedules = DB::table('mssql_schedulemaster')
            ->select('ProviderID', DB::raw('MAX(ScheduleDate) as ScheduleDate'))
            ->whereIn('ScheduleStatusID', $scheduleStatuses)
            ->groupBy('ProviderID');

        $userStatus = UserStatus::ACTIVE->value;

        $roleId = RolesEnum::ORIENTATION_COORDINATOR->value;
        $ocSubquery = "
        (SELECT users.id
         FROM users
         INNER JOIN users_has_roles ON users.id = users_has_roles.user_id
         INNER JOIN roles ON users_has_roles.role_id = roles.id
         WHERE users_has_roles.role_id = {$roleId}
           AND users.status = {$userStatus}
           AND FIND_IN_SET(mf.branchid, users.branchid)
         LIMIT 1) as oc
    ";

        return DB::table('mssql_providers as mp')
            ->leftJoin('mssql_facilities as mf', 'mp.FacilityID', '=', 'mf.FacilityID')
            ->leftJoinSub($latestSchedules, 'ls', 'mp.ProviderID', '=', 'ls.ProviderID')
            ->select(
                'mp.id',
                'mp.EmploymentStatusID',
                'mp.request_outcome',
                DB::raw('DATE(mp.request_last_followup) as request_last_followup'),
                DB::raw("{$diffExpr} as diff"),
                DB::raw($ocSubquery)
            )
            ->whereIn('mp.EmploymentStatusID', $employmentStatuses)
            ->where('mp.is_deleted', 0)
            ->where('mp.IsOfficeStaff', '!=', 1)
            ->whereRaw('IFNULL(mp.request_outcome, "") <> ?', [self::REQUESTED_OUTCOME])
            ->whereRaw('LOWER(mp.DisciplineName) NOT LIKE ?', ['%msw%'])
            ->where(function ($query) use ($hireDateExpr) {
                $query
                    ->whereRaw("{$hireDateExpr} < DATE(NOW() - INTERVAL 1 YEAR)")
                    ->orWhereNull('ls.ScheduleDate');
            })
            ->havingRaw('diff > ?', [$daysInYear]);
    }
}
