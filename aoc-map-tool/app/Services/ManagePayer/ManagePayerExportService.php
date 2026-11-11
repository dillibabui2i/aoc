<?php

namespace App\Services\ManagePayer;

use App\Models\GrossMarginConfiguration;
use App\Models\PaymentSource;
use App\MssqlFacilities;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagePayerExportService
{
    /**
     * @param $request
     * @return Builder
     */
    private function getPayerList4Export($request): Builder
    {
        $payerList = PaymentSource::query()
            ->with(['grossMarginConfigurations'])
            ->whereRaw('IFNULL(is_deleted, 0) <> 1');

        if (isset($request->search['value']) && $request->search['value'] != '') {
            $payerList = $payerList->where('name', 'like', "%{$request->search['value']}%");
        }

        $payerList = $payerList->orderBy('name');

        return $payerList;
    }

    /**
     * @param $request
     * @return StreamedResponse
     */
    public function exportPayers($request): StreamedResponse
    {
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=payerList.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $payerList = $this->getPayerList4Export($request);

        $data = $payerList->get();
        $result = $this->buildGrossMarginCsvCallback($data);

        return response()->stream($result, Response::HTTP_OK, $headers);
    }

    /**
     * @param $payers
     * @return Closure
     */
    private function buildGrossMarginCsvCallback($payers): Closure
    {
        $allBranchIds = $payers
            ->flatMap(fn($p) => $p->grossMarginConfigurations)
            ->pluck('branch_ids')
            ->filter()
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        $branchesMap = !empty($allBranchIds)
            ? MssqlFacilities::query()->whereIn('id', $allBranchIds)->get()->keyBy('id')
            : collect();

        return function () use ($payers, $branchesMap) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Payer Name',
                'State',
                'LOB',
                'Branch',
                'Recruiter & Coverage Target',
                'Recruiter Escalated',
                'TL/Manager Escalated',
                'TL/Manager Whole Home Escalated',
                'Exec Team Escalated',
                'Coverage Escalated',
                'Coverage TL/Manager Escalated',
                'Coverage TL/Manager Whole Home Escalated',
                'Coverage Exec Team Escalated',
            ], ';');

            foreach ($payers as $payer) {
                $configurations = $payer->grossMarginConfigurations;

                if ($configurations->isEmpty()) {
                    fputcsv($file, $this->buildRow($payer, null, ''), ';');
                    continue;
                }

                foreach ($configurations as $gmc) {
                    $branchIds = $gmc->branch_ids ?? [];

                    if (empty($branchIds)) {
                        fputcsv($file, $this->buildRow($payer, $gmc, ''), ';');
                        continue;
                    }

                    foreach ($branchIds as $branchId) {
                        $branchName = $branchesMap->get($branchId)?->FacilityName ?? '';
                        fputcsv($file, $this->buildRow($payer, $gmc, $branchName), ';');
                    }
                }
            }

            fclose($file);
        };
    }

    /**
     * @param PaymentSource $payer
     * @param GrossMarginConfiguration|null $gmc
     * @param string $branchName
     * @return array
     */
    private function buildRow(PaymentSource $payer, ?GrossMarginConfiguration $gmc, string $branchName): array
    {
        return [
            $payer->name ?? '',
            $payer->state ?? '',
            $gmc?->lob_enum?->label() ?? '',
            $branchName,
            $gmc->recruiter_coverage_target ?? '',
            $gmc->recruiter_escalated ?? '',
            $gmc->tl_manager_escalated ?? '',
            $gmc->tl_manager_whole_home_escalated ?? '',
            $gmc->exec_team_escalated ?? '',
            $gmc->coverage_escalated ?? '',
            $gmc->coverage_tl_manager_escalated ?? '',
            $gmc->coverage_tl_manager_whole_home_escalated ?? '',
            $gmc->coverage_exec_team_escalated ?? '',
        ];
    }
}
