<?php

namespace App\Services\ManagePayer;

use App\DTO\ManagePayer\ManagePayerParamsDTO;
use App\Helpers\H;
use App\Enums\PayerRatesStatus;
use App\Models\GrossMarginConfiguration;
use App\Models\PaymentSource;
use App\PayerRates;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ManagePayerService
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function buildPayersTable(Request $request): JsonResponse
    {
        $payerList = $this->buildPayerQuery($request);

        return DataTables::of($payerList)
            ->editColumn('state', function ($item) {
                return $item->state ?? 'None';
            })
            ->addColumn('margin_configuration', function ($item) {
                return $this->getGrossMarginConfigurationStatus($item->id);
            })
            ->addColumn('action', function ($item) {
                return view('managepayer.partials.tableAction', compact('item'))->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * @param $request
     * @return Builder
     */
    private function buildPayerQuery($request): Builder
    {
        $payerList = DB::table('payment_source')
            ->select('payment_source.*')
            ->whereRaw('IFNULL(payment_source.is_deleted, 0) <> 1');

        if (isset($request->search['value']) && $request->search['value'] != '') {
            $payerList = $payerList->where(function ($query) use ($request) {
                $searchValue = $request['search']['value'];
                $query->where('payment_source.name', 'like', "%{$searchValue}%");
            });
        }

        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $payerList = $payerList->orderBy('name', $request['order'][0]['dir']);
        }

        return $payerList;
    }

    /**
     * @param ManagePayerParamsDTO $params
     * @return void
     * @throws \Throwable
     */
    public function saveGrossMarginConfiguration(ManagePayerParamsDTO $params): void
    {
        DB::transaction(function () use ($params) {

            $payer = $params->id;
            $rows = $params->rows ?? [];

            foreach ($rows as $row) {
                if (empty($row->lob) || empty($row->branchIds)) {
                    continue;
                }

                $branchIds = array_map('intval', $row->branchIds ?? []);
                sort($branchIds);

                $data = [
                    'payer_id' => $payer,
                    'lob_enum' => $row->lob->value,
                    'branch_ids' => $branchIds,
                    'recruiter_coverage_target' => $row->recruiterCoverageTarget ?? null,
                    'recruiter_escalated' => $row->recruiterEscalated ?? null,
                    'tl_manager_escalated' => $row->tlManagerEscalated ?? null,
                    'tl_manager_whole_home_escalated' => $row->tlManagerWholeHomeEscalated ?? null,
                    'exec_team_escalated' => $row->execTeamEscalated ?? null,
                    'coverage_escalated' => $row->coverageEscalated ?? null,
                    'coverage_tl_manager_escalated' => $row->coverageTlManagerEscalated ?? null,
                    'coverage_tl_manager_whole_home_escalated' => $row->coverageTlManagerWholeHomeEscalated ?? null,
                    'coverage_exec_team_escalated' => $row->coverageExecTeamEscalated ?? null,
                ];

                if (!empty($row->id)) {
                    GrossMarginConfiguration::where('id', $row->id)->update($data);
                } else {
                    GrossMarginConfiguration::create($data);
                }
            }
        });
    }

    /**
     * @param ManagePayerParamsDTO $data
     * @return bool
     */
    public function isGrossMarginConfigurationExist(ManagePayerParamsDTO $data)
    {
        $rows = $data->rows ?? [];
        $payerId = $data->id;

        foreach ($rows as $row) {

            if (empty($row->lob) || empty($row->branchIds)) {
                continue;
            }

            if (!empty($row->id)) {
                continue;
            }

            $branchIds = array_map('intval', $row->branchIds);
            sort($branchIds);

            $existing = GrossMarginConfiguration::query()
                ->where('lob_enum', $row->lob->value)
                ->where('payer_id', $payerId)
                ->get();

            foreach ($existing as $config) {
                $existingBranchIds = array_map('intval', $config->branch_ids);
                sort($existingBranchIds);

                if ($branchIds === $existingBranchIds) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param int $payerId
     * @return string
     */
    public function getGrossMarginConfigurationStatus(int $payerId): string
    {
        $configs = GrossMarginConfiguration::query()
            ->where('payer_id', $payerId)
            ->get();

        if ($configs->isEmpty()) {
            return 'Not configured';
        }

        $fields = [
            'recruiter_coverage_target',
            'recruiter_escalated',
            'tl_manager_escalated',
            'tl_manager_whole_home_escalated',
            'exec_team_escalated',

            'coverage_escalated',
            'coverage_tl_manager_escalated',
            'coverage_tl_manager_whole_home_escalated',
            'coverage_exec_team_escalated',
        ];

        $first = $configs->first();

        foreach ($configs as $config) {
            foreach ($fields as $field) {
                if ((string)$config->$field !== (string)$first->$field) {
                    return 'Branch Specific';
                }
            }
        }

        return 'Standard';
    }

    /**
     * @param bool $isRespite
     * @return array
     */
    public function getPayers(bool $isRespite = false): array
    {
        $operator = $isRespite ? 'LIKE' : 'NOT LIKE';
        $today = now();

        // Kantime payers
        $kantimePayers = PaymentSource::query()
            ->where('name', $operator, '%respite%')
            ->distinct()
            ->pluck('name');

        // Custom payers from payer_rates
        $customPayers = PayerRates::query()
            ->where('is_custom', 1)
            ->where('is_deleted', 0)
            ->where('status', PayerRatesStatus::APPROVED->value)
            ->whereDate('effectiveFrom', '<=', $today)
            ->whereDate('effectiveThrough', '>=', $today)
            ->where('payer', $operator, '%respite%')
            ->distinct()
            ->pluck('payer');

        return $kantimePayers
            ->merge($customPayers)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->map(function ($payer) {
                return [
                    'value' => $payer,
                    'text' => $payer,
                ];
            })
            ->toArray();
    }

    /**
     * Active branches (same list as the Gross Margin configuration) as dropdown options.
     *
     * @return array<int, array{value: int, text: string}>
     */
    public function getBranches(): array
    {
        return H::getHelper('ConstantHelper')
            ->getFacilities()
            ->map(fn ($branch) => [
                'value' => $branch->id,
                'text' => $branch->FacilityName,
            ])
            ->values()
            ->toArray();
    }
}
