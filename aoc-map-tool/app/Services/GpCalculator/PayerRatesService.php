<?php

namespace App\Services\GpCalculator;

use App\DTO\GpCalculator\RequestPayerRateDTO;
use App\Enums\PayerRatesStatus;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Notifications;
use App\PayerRates;
use App\PayerRateService;
use App\Services\DisciplineRateMap;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class PayerRatesService
{
    public function __construct(private DisciplineRateMap $disciplineRateMap)
    {
    }

    /**
     * Get payer rate. If payer rate is configured in the system, then rate will be overridden.
     * @param RequestPayerRateDTO $payerRateRequest
     * @return float
     */
    public function getPayerRate(RequestPayerRateDTO $payerRateRequest) : float
    {
        $rate = 0;
        $payerList = DB::table('service_codes_daas')
            ->join('payment_source', 'payment_source.payersource_id', '=', 'service_codes_daas.payerid')
            ->select('service_codes_daas.*')
            ->where(DB::raw('ifnull(service_codes_daas.is_deleted,0)'), 0)
            ->where('payment_source.name', $payerRateRequest->getPaymentSourceName())
            ->where('service_codes_daas.name', $payerRateRequest->getServiceCodeName())
            ->first();

        //Get base rate
        if (!empty($payerList)) {
            $rate = $payerList->ContractualRate * (60 / $payerList->MinuteInUnit);
        }
        //Get overridden rate based on system rates configuration or fallback to default service rate
        $rate = $this->getOverriddenRate($payerRateRequest) ?? $rate;

        //Get special rate or fallback to overridden or to default service rate
        return $this->getSpecializedRate($payerRateRequest) ?? $rate;
    }

    /**
     * In this action we are using payer rates names normalization to find a reasonable match
     *  Firstly we are picking up base rates from service codes, then we are applying overrides from the platform service codes configuration.
     * @param RequestPayerRateDTO $payerRateRequest
     * @return float|null
     */
    private function getOverriddenRate(RequestPayerRateDTO $payerRateRequest) : float|null
    {
        $discipline = stripcslashes(strtoupper($payerRateRequest->getDiscipline()));
        $rateColumn = $this->disciplineRateMap->rateFor($discipline);
        $rateColumn = $rateColumn instanceof \App\Enums\PayerRates ? $rateColumn->value : $rateColumn;
        $currentDate = date('Y-m-d');

        $overridePayer = PayerRates::query()
            ->with(['services' => function ($query) use ($discipline) {
                $query->where('service_name', $discipline);
            }])
            ->join('payment_source', 'payment_source.payersource_id', '=', 'payer_rates.payer_id')
            ->select('payer_rates.*')
            ->where('payer_rates.is_deleted', 0)
            ->where('payment_source.name', $payerRateRequest->getPaymentSourceName())
            ->where('payer_rates.status', 1)
            ->where(DB::raw('DATE(payer_rates.effectiveThrough)'), '>=', $currentDate)
            ->where(DB::raw('DATE(payer_rates.effectiveFrom)'), '<=', $currentDate)->first();

        if (!$overridePayer) {
            $overridePayer = PayerRates::query()
                ->with(['services' => function ($query) use ($discipline) {
                    $query->where('service_name', $discipline);
                }])
                ->where('is_custom', 1)
                ->where('is_deleted', 0)
                ->where('status', PayerRatesStatus::APPROVED)
                ->where('payer', $payerRateRequest->getPaymentSourceName())
                ->whereDate('effectiveThrough', '>=', now())
                ->whereDate('effectiveFrom', '<=', now())
                ->first();
        }
        if (!$overridePayer) {
            H::ExcelLog(sprintf('No custom rate found for: `%s`', $payerRateRequest->getPaymentSourceName()), 'gpCalculation.log');
        }

        //Trying to find propper override rule
        if (!empty($overridePayer)) {

            if(LD::checkFeatureFlag('add-scalable-service-manage-payer-rates')){
                return $overridePayer->services->first()?->rate;
            }

            if ($rateColumn && $this->checkPayerRateIsSet($overridePayer, $rateColumn)) {
                H::ExcelLog(sprintf('Got custom rate `$%s` for `%s` unsing `%s` column', $overridePayer->$rateColumn, $discipline, $rateColumn), 'gpCalculation.log');
                return $overridePayer->$rateColumn;
            }
            H::ExcelLog(sprintf('No custom rate applied for `%s`', $discipline), 'gpCalculation.log');
        }

        return null;
    }

    private function checkPayerRateIsSet(object $payer, $property) : bool
    {
        if (isset($payer->$property) && $payer->$property !== '') {
            return true;
        }

        return false;
    }

    /**
     * Get specialized rate.
     * @param RequestPayerRateDTO $payerRateRequest
     * @return float|null
     */
    private function getSpecializedRate(RequestPayerRateDTO $payerRateRequest) : float|null
    {
        $mapArray = [
            'SCA_RN_SP_Rate' => 'RN Specialized',
            'SCA_RN_Rate' => 'RN',
            'SCA_LVN_LPN_SP_Rate' => 'LVN/LPN Specialized',
            'SCA_LVN_LPN_Rate' => 'LVN/LPN',
            'SCA_CNA_Rate' => 'CNA',
        ];

        $specialPayerGroup = DB::table('special_payer_group')
            ->select('special_payer_group.*')
            ->where('special_payer_group.PatientAccount', $payerRateRequest->getClientInfo()->PatientAccount)
            ->first();

        if (!empty($specialPayerGroup)) {
            $foundKey = null;

            foreach ($mapArray as $key => $value) {
                // Check if $value is present inside $disciplineString (case-insensitive)
                if (stripos($payerRateRequest->getDiscipline(), $value) !== false) {
                    $foundKey = $key;
                    break; // Stop at the first match
                }
            }

            if ($foundKey !== null) {
                return isset($specialPayerGroup->{$foundKey}) ? $specialPayerGroup->{$foundKey} : null;
            }
        }

        return null;
    }

    public function update(array $data, array $pendingFields): void
    {
        $id = $data['id'];
        $payerRate = PayerRates::with('services:id,payer_rate_id,service_name,rate')
            ->findOrFail($id);

        $oldData = $payerRate->only($pendingFields);

        $newData = Arr::only($data, $pendingFields);

        if (isset($newData['effectiveFrom'])) {
            $newData['effectiveFrom'] = Carbon::createFromFormat('m/d/Y', $newData['effectiveFrom'])
                ->format('Y-m-d');
        }
        if (isset($newData['effectiveThrough'])) {
            $newData['effectiveThrough'] = Carbon::createFromFormat('m/d/Y', $newData['effectiveThrough'])
                ->format('Y-m-d');
        }

        foreach ($newData as $field => $newValue) {
            $oldValue = $oldData[$field] ?? null;

            if (in_array($field, ['effectiveFrom', 'effectiveThrough'])) {
                $oldValue = $oldValue ? getFormatedDate($oldValue) : $oldValue;
                $newValue = $newValue ? getFormatedDate($newValue) : $newValue;
            }

            if ($oldValue != $newValue) {
                $log = Auth::user()->name . " updated {$field} from {$oldValue} to {$newValue}";
                H::getHelper('ActivityNotes')->setActivityNotes([
                    'entity' => 'payer-rate',
                    'entity_id' => $id,
                    'notes' => $log,
                ]);
            }
        }

        if ($payerRate->status === PayerRatesStatus::APPROVED->value) {
            $payerRate->pending_data = $newData;
        } else {
            $payerRate->fill($newData);
        }
        $payerRate->denied_at = null;
        $payerRate->denied_by = null;
        if ($payerRate->status === PayerRatesStatus::DENIED->value) {
            $payerRate->status = PayerRatesStatus::PENDING->value;
        };
        $payerRate->save();


        if (LD::checkFeatureFlag('add-scalable-service-manage-payer-rates') && isset($data['services'])) {
            $userName = Auth::user()->name;
            $oldServicesMap = collect($payerRate->services)->pluck('rate', 'service_name')->toArray();
            $newServicesMap = collect($data['services'])->pluck('rate', 'service_name')->toArray();

            $this->logServiceChanges($id, $userName, $oldServicesMap, $newServicesMap);

            $deletedItemsKeys = array_keys(array_diff_key($oldServicesMap, $newServicesMap));
            if (!empty($deletedItemsKeys)) {
                PayerRateService::where('payer_rate_id', $payerRate->id)
                    ->whereIn('service_name', $deletedItemsKeys)
                    ->update(['pending_deletion' => 1]);
            }

            $now = now();
            foreach ($data['services'] as $serviceRelation) {
                DB::table('payer_rate_services')->updateOrInsert(
                    ['payer_rate_id' => $payerRate->id, 'service_name' => $serviceRelation['service_name']],
                    ['pending_rate' => $serviceRelation['rate'], 'updated_at' => $now, 'pending_deletion' => 0]
                );
            }
        }

        $loggedInUser = H::getLoggedInUser();
        $userlist = H::getHelper("UserPermissionResolver")->getUserByPermission('get_reimbursement_notification');
        if (count($userlist) > 0) {
            foreach ($userlist as $user) {
                $notification = [
                    "related_item" => $payerRate->id,
                    "type" => 'ReimbursementRequest',
                    "title" => "Notification of Request for Reimbursement Change",
                    "notes" => "" . $loggedInUser['name'] . " has requested the reimbursement rate for " . $payerRate->payer . " be updated. Please review and approve or deny the request",
                    "user_id" => $user->id,
                ];
                Notifications::addNotification($notification);
            }
        }
    }

    private function logServiceChanges(int $entityId, string $userName, array $oldServicesMap, array $newServicesMap): void
    {
        // Deleted services
        $deletedServices = array_diff_key($oldServicesMap, $newServicesMap);
        foreach ($deletedServices as $serviceName => $oldRate) {
            H::getHelper('ActivityNotes')->setActivityNotes([
                'entity' => 'payer-rate',
                'entity_id' => $entityId,
                'notes' => "{$userName} deleted service '{$serviceName}' (previous rate: {$oldRate})",
            ]);
        }

        // Added services
        $addedServices = array_diff_key($newServicesMap, $oldServicesMap);
        foreach ($addedServices as $serviceName => $newRate) {
            H::getHelper('ActivityNotes')->setActivityNotes([
                'entity' => 'payer-rate',
                'entity_id' => $entityId,
                'notes' => "{$userName} added service '{$serviceName}' with rate: {$newRate}",
            ]);
        }

        // Updated services
        $commonServices = array_intersect_key($newServicesMap, $oldServicesMap);
        foreach ($commonServices as $serviceName => $newRate) {
            $oldRate = $oldServicesMap[$serviceName];
            if ((float) $oldRate !== (float) $newRate) {
                H::getHelper('ActivityNotes')->setActivityNotes([
                    'entity' => 'payer-rate',
                    'entity_id' => $entityId,
                    'notes' => "{$userName} updated service '{$serviceName}' rate from {$oldRate} to {$newRate}",
                ]);
            }
        }
    }
}
