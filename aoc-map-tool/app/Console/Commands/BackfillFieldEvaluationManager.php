<?php

namespace App\Console\Commands;

use App\Enums\MssqlScheduleMasterStatus;
use App\Enums\RolesEnum;
use App\Enums\UserStatus;
use App\Helpers\H;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off backfill for field_evaluations.manager on this year's non-CO evaluations.
 *
 * mssql_providers.ccm_id (used at generation time) is always null, so `manager`
 * is never populated. The real manager is only ever computed on the fly, at send
 * time, in RightSignatureController::generateAndSendNonTherapyPDF() via
 * H::getHelper("RightSignature")->getCCMFromProvider(), and never persisted.
 *
 * This command replicates that same resolution logic (see
 * app/Helpers/RightSignature.php getCCMFromProvider()/getDBORDOByClinician()),
 * but resolves to a users.id instead of a users.name, since field_evaluations.manager
 * is an integer FK to users.id (unlike the PDF text field). It intentionally does not
 * touch the existing helper, which is still used unchanged for PDF generation.
 */
class BackfillFieldEvaluationManager extends Command
{
    protected $signature = 'fieldevaluation:backfill-manager';

    protected $description = 'Backfill manager (users.id) on this year\'s non-CO, non-therapy field evaluations using the same manager resolution generateAndSendNonTherapyPDF would use today';

    private const CHUNK_SIZE = 500;
    private const GA_ATLANTA_STATE = 'GA - Atlanta';

    public function handle()
    {
        $erinSmithId = DB::table('users')->where('name', 'Erin Smith')->value('id');

        $scheduleStartDate = date('Y-01-04', strtotime('-1 Year'));
        $scheduleEndDate = date('Y-01-03');

        [$dboByBranch, $rdoByBranch] = $this->buildBranchManagerMaps();

        $stats = [
            'ga_atlanta' => 0,
            'ccm' => 0,
            'dbo' => 0,
            'rdo' => 0,
            'unresolved' => 0,
        ];

        DB::table('field_evaluations')
            ->select(
                'field_evaluations.id',
                'mssql_providers.ProviderID',
                'mssql_facilities.FacilityName',
                'mssql_facilities.branchid'
            )
            ->leftJoin('mssql_providers', 'field_evaluations.aoc_provider_id', '=', 'mssql_providers.id')
            ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
            ->whereRaw('YEAR(field_evaluations.created_at) = YEAR(NOW())')
            ->whereNull('field_evaluations.manager')
            ->where('mssql_facilities.State', '!=', 'CO')
            ->whereNotIn('mssql_providers.DisciplineName', H::getTherapyEvaluationDiscipline())
            ->where('mssql_providers.is_deleted', 0)
            ->whereNested(function ($q) {
                $q->where('field_evaluations.is_deleted', 0)
                    ->orWhereNull('field_evaluations.is_deleted');
            })
            ->orderBy('field_evaluations.id')
            ->chunk(self::CHUNK_SIZE, function ($rows) use (
                $erinSmithId,
                $scheduleStartDate,
                $scheduleEndDate,
                $dboByBranch,
                $rdoByBranch,
                &$stats
            ) {
                $this->processChunk($rows, $erinSmithId, $scheduleStartDate, $scheduleEndDate, $dboByBranch, $rdoByBranch, $stats);
            });

        $this->info('Done. ' . json_encode($stats));
        H::ExcelLog('BackfillFieldEvaluationManager finished: ' . print_r($stats, true), 'FieldEvaluation.log');
    }

    private function processChunk($rows, $erinSmithId, $scheduleStartDate, $scheduleEndDate, $dboByBranch, $rdoByBranch, array &$stats)
    {
        $gaAtlantaEvalIds = [];
        $lookupRows = [];
        foreach ($rows as $row) {
            if ($row->FacilityName === self::GA_ATLANTA_STATE) {
                $gaAtlantaEvalIds[] = $row->id;
            } else {
                $lookupRows[] = $row;
            }
        }

        if (!empty($gaAtlantaEvalIds) && $erinSmithId) {
            DB::table('field_evaluations')->whereIn('id', $gaAtlantaEvalIds)->update(['manager' => $erinSmithId]);
            $stats['ga_atlanta'] += count($gaAtlantaEvalIds);
        }

        if (empty($lookupRows)) {
            return;
        }

        $providerIds = array_values(array_unique(array_map(fn ($r) => $r->ProviderID, $lookupRows)));

        $ccmByProviderId = $this->resolveCcmByProviderId($providerIds, $scheduleStartDate, $scheduleEndDate);

        $updatesByManagerId = [];
        foreach ($lookupRows as $row) {
            $managerId = $ccmByProviderId[$row->ProviderID] ?? null;
            if ($managerId) {
                $stats['ccm']++;
            } elseif (!empty($dboByBranch[$row->branchid])) {
                $managerId = $dboByBranch[$row->branchid];
                $stats['dbo']++;
            } elseif (!empty($rdoByBranch[$row->branchid])) {
                $managerId = $rdoByBranch[$row->branchid];
                $stats['rdo']++;
            } else {
                $stats['unresolved']++;
                continue;
            }

            $updatesByManagerId[$managerId][] = $row->id;
        }

        foreach ($updatesByManagerId as $managerId => $evalIds) {
            DB::table('field_evaluations')->whereIn('id', $evalIds)->update(['manager' => $managerId]);
        }
    }

    /**
     * Mirrors RightSignature::getCCMFromProvider()'s schedule/patient lookup, but
     * batched across all ProviderIDs in the chunk in a single query and resolved
     * to mssql_patients.ccm_id (already a users.id) instead of users.name.
     */
    private function resolveCcmByProviderId(array $providerIds, string $scheduleStartDate, string $scheduleEndDate): array
    {
        $hoursByProviderAndPatient = DB::table('mssql_schedulemaster')
            ->select(
                'mssql_schedulemaster.ProviderID',
                'mssql_schedulemaster.PatientID',
                'mssql_patients.ccm_id',
                DB::raw('sum(Hours) as totalhours')
            )
            ->leftJoin('mssql_patients', 'mssql_patients.PatientID', '=', 'mssql_schedulemaster.PatientID')
            ->whereIn('mssql_schedulemaster.ProviderID', $providerIds)
            ->whereIn('ScheduleStatusID', [
                MssqlScheduleMasterStatus::APPROVED->value,
                MssqlScheduleMasterStatus::COMPLETED->value,
                MssqlScheduleMasterStatus::IN_PROGRESS->value
            ])
            ->whereDate('ScheduleDate', '>=', $scheduleStartDate)
            ->whereDate('ScheduleDate', '<=', $scheduleEndDate)
            ->whereRaw('ifnull(mssql_patients.ccm_id,0) != 0 and ifnull(mssql_schedulemaster.PatientID,0) != 0')
            ->groupBy('mssql_schedulemaster.ProviderID', 'mssql_schedulemaster.PatientID', 'mssql_patients.ccm_id')
            ->get();

        $bestPerProvider = [];
        foreach ($hoursByProviderAndPatient as $row) {
            $current = $bestPerProvider[$row->ProviderID] ?? null;
            if (!$current || $row->totalhours > $current->totalhours) {
                $bestPerProvider[$row->ProviderID] = $row;
            }
        }

        $ccmByProviderId = [];
        foreach ($bestPerProvider as $providerId => $row) {
            $ccmByProviderId[$providerId] = $row->ccm_id;
        }

        return $ccmByProviderId;
    }

    /**
     * Mirrors RightSignature::getDBORDOByClinician(), but returns branchid => userId
     * maps covering every branch in one query each, instead of one query per provider.
     */
    private function buildBranchManagerMaps(): array
    {
        $dboByBranch = $this->buildManagerMapForRole(
            RolesEnum::getRoleName(RolesEnum::DIRECTOR_OF_BRANCH_OPERATIONS->value)
        );

        $rdoByBranch = $this->buildManagerMapForRole(
            RolesEnum::getRoleName(RolesEnum::REGIONAL_DIRECTOR_OF_OPERATIONS->value)
        );

        return [$dboByBranch, $rdoByBranch];
    }

    private function buildManagerMapForRole(string $roleName): array
    {
        $users = DB::table('users')
            ->select('users.id', 'users.branchid')
            ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
            ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', $roleName)
            ->where('users.status', UserStatus::ACTIVE->value)
            ->whereNotNull('users.branchid')
            ->get();

        $map = [];
        foreach ($users as $user) {
            foreach (explode(',', $user->branchid) as $branchid) {
                $branchid = trim($branchid);
                if ($branchid !== '' && !isset($map[$branchid])) {
                    $map[$branchid] = $user->id;
                }
            }
        }

        return $map;
    }
}
