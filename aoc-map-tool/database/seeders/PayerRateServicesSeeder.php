<?php

namespace Database\Seeders;

use App\Enums\Disciplines;
use App\Models\Adjustment;
use App\PayerRates;
use App\Enums\PayerRates as PayerRatesEnum;
use App\Services\DisciplineRateMap;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayerRateServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $MAP = [
        // HHA GROUP PRIMARY
        PayerRatesEnum::HHA_PRIMARY_GROUP->value => [
            Disciplines::HHA_GROUP_PRIMARY->value,
        ],

        // CNA GROUP PRIMARY
        PayerRatesEnum::CNA_PRIMARY_GROUP->value => [
            Disciplines::CNA_GROUP_PRIMARY->value,
        ],

        // LVN_LPN
        PayerRatesEnum::LVN_LPN->value => [

            Disciplines::LVN_LPN->value,
            Disciplines::COVERAGE_LVN_LPN->value,
            Disciplines::COVERAGE_LVN_LPN_MDCP->value,
            Disciplines::LVN_LPN_MDCP->value,

            Disciplines::RESPITE_LVN_LPN->value,
            Disciplines::RESPITE_COVERAGE_LVN_LPN->value,
            Disciplines::RESPITE_COVERAGE_LVN_LPN_MDCP->value,
            Disciplines::RESPITE_COVERAGE_RN_TO_LVN_MDCP->value,
            Disciplines::RESPITE_LVN_LPN_MDCP->value
        ],

        // LVN_LPN_SPECIAL
        PayerRatesEnum::LVN_LPN_SPECIAL->value => [
            Disciplines::LVN_LPN_SPECIALIZED->value,
            Disciplines::LVN_LPN_SPECIALIZED_MDCP->value,
            Disciplines::COVERAGE_LVN_LPN_SPECIALIZED->value,
            Disciplines::COVERAGE_LVN_LPN_SPECIALIZED_MDCP->value,

            Disciplines::RESPITE_LVN_LPN_SPECIALIZED->value,
            Disciplines::RESPITE_LVN_LPN_SPECIALIZED_MDCP->value,
            Disciplines::RESPITE_COVERAGE_LVN_LPN_SPECIALIZED->value,
            Disciplines::RESPITE_COVERAGE_LVN_LPN_SPECIALIZED_MDCP->value,
        ],
        // RN
        PayerRatesEnum::RN->value => [
            Disciplines::RN->value,
            Disciplines::RN_MDCP->value,
            Disciplines::RN_TO_LVN_LPN->value,
            Disciplines::RN_TO_LVN_LPN_MDCP->value,
            Disciplines::COVERAGE_RN->value,
            Disciplines::COVERAGE_RN_MDCP->value,
            Disciplines::COVERAGE_RN_TO_LVN_LPN->value,
            Disciplines::COVERAGE_RN_TO_LVN_LPN_MDCP->value,

            Disciplines::RESPITE_RN->value,
            Disciplines::RESPITE_RN_MDCP->value,
            Disciplines::RESPITE_RN_TO_LVN->value,
            Disciplines::RESPITE_RN_TO_LVN_MDCP->value,
            Disciplines::RESPITE_COVERAGE_RN->value,
            Disciplines::RESPITE_COVERAGE_RN_MDCP->value,
            Disciplines::RESPITE_COVERAGE_RN_TO_LVN->value,
        ],
        //RN_SPECIAL
        PayerRatesEnum::RN_SPECIAL->value => [
            Disciplines::RN_SPECIALIZED->value,
            Disciplines::RN_SPECIALIZED_MDCP->value,
            Disciplines::RN_TO_LVN_SPECIALIZED->value,
            Disciplines::RN_TO_LVN_SPECIALIZED_MDCP->value,
            Disciplines::COVERAGE_RN_SPECIALIZED->value,
            Disciplines::COVERAGE_RN_SPECIALIZED_MDCP->value,
            Disciplines::COVERAGE_RN_TO_LVN_SPECIALIZED_MDCP->value,
            Disciplines::COVERAGE_RN_TO_LVN_SPECIALIZED->value,

            Disciplines::RESPITE_RN_SPECIALIZED->value,
            Disciplines::RESPITE_RN_SPECIALIZED_MDCP->value,
            Disciplines::RESPITE_RN_TO_LVN_SPECIALIZED->value,
            Disciplines::RESPITE_RN_TO_LVN_SPECIALIZED_MDCP->value,
            Disciplines::RESPITE_COVERAGE_RN_SPECIALIZED->value,
            Disciplines::RESPITE_COVERAGE_RN_SPECIALIZED_MDCP->value,
            Disciplines::RESPITE_COVERAGE_RN_TO_LVN_SPECIALIZED_MDCP->value,
            Disciplines::RESPITE_COVERAGE_RN_TO_LVN_SPECIALIZED->value,
        ]
    ];

        $serviceNames = DB::table('service_codes_daas as SVM')
            ->distinct()
            ->orderBy('name')
            ->pluck('SVM.name')
            ->toArray();
        $lowercaseLookupMap = [];
        foreach ($serviceNames as $name) {
            $lowercaseLookupMap[mb_strtoupper($name)] = $name;
        }

        $payerRates = PayerRates::all();

        foreach ($payerRates as $payerRate) {
            foreach ($MAP as $column => $disciplines) {
                foreach ($disciplines as $discipline) {
                    if($payerRate->$column != null && $payerRate->$column != 0.00){
                        $finalServiceName = $lowercaseLookupMap[$discipline] ?? $discipline;
                        DB::table('payer_rate_services')->insert([
                            'payer_rate_id' => $payerRate->id,
                            'service_name' => $finalServiceName,
                            'rate' => $payerRate->$column ?? null,
                        ]);
                    }
                }
            }
        }
    }
}
