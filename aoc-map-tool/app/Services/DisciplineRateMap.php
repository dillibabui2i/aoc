<?php

namespace App\Services;

use App\Enums\Disciplines;
use App\Enums\PayerRates;

/**
 * Discipline -> PayerRates mapping (manual reimbursement rate bucket).
 */
final class DisciplineRateMap
{
    /**
     * @var array<string, PayerRates> key is Disciplines::value
     */
    private const MAP = [
        // HHA GROUP PRIMARY
        Disciplines::HHA_GROUP_PRIMARY->value => PayerRates::HHA_PRIMARY_GROUP,

        // CNA GROUP PRIMARY
        Disciplines::CNA_GROUP_PRIMARY->value => PayerRates::CNA_PRIMARY_GROUP,

        // LVN_LPN
        Disciplines::LVN_LPN->value => PayerRates::LVN_LPN,
        Disciplines::COVERAGE_LVN_LPN->value => PayerRates::LVN_LPN,
        Disciplines::COVERAGE_LVN_LPN_MDCP->value => PayerRates::LVN_LPN,
        Disciplines::LVN_LPN_MDCP->value => PayerRates::LVN_LPN,

        Disciplines::RESPITE_LVN_LPN->value => PayerRates::LVN_LPN,
        Disciplines::RESPITE_COVERAGE_LVN_LPN->value => PayerRates::LVN_LPN,
        Disciplines::RESPITE_COVERAGE_LVN_LPN_MDCP->value => PayerRates::LVN_LPN,
        Disciplines::RESPITE_COVERAGE_RN_TO_LVN_MDCP->value => PayerRates::LVN_LPN,
        Disciplines::RESPITE_LVN_LPN_MDCP->value => PayerRates::LVN_LPN,

        // LVN_LPN_SPECIAL
        Disciplines::LVN_LPN_SPECIALIZED->value => PayerRates::LVN_LPN_SPECIAL,
        Disciplines::LVN_LPN_SPECIALIZED_MDCP->value => PayerRates::LVN_LPN_SPECIAL,
        Disciplines::COVERAGE_LVN_LPN_SPECIALIZED->value => PayerRates::LVN_LPN_SPECIAL,
        Disciplines::COVERAGE_LVN_LPN_SPECIALIZED_MDCP->value => PayerRates::LVN_LPN_SPECIAL,

        Disciplines::RESPITE_LVN_LPN_SPECIALIZED->value => PayerRates::LVN_LPN_SPECIAL,
        Disciplines::RESPITE_LVN_LPN_SPECIALIZED_MDCP->value => PayerRates::LVN_LPN_SPECIAL,
        Disciplines::RESPITE_COVERAGE_LVN_LPN_SPECIALIZED->value => PayerRates::LVN_LPN_SPECIAL,
        Disciplines::RESPITE_COVERAGE_LVN_LPN_SPECIALIZED_MDCP->value => PayerRates::LVN_LPN_SPECIAL,

        // RN
        Disciplines::RN->value => PayerRates::RN,
        Disciplines::RN_MDCP->value => PayerRates::RN,
        Disciplines::RN_TO_LVN_LPN->value => PayerRates::RN,
        Disciplines::RN_TO_LVN_LPN_MDCP->value => PayerRates::RN,
        Disciplines::COVERAGE_RN->value => PayerRates::RN,
        Disciplines::COVERAGE_RN_MDCP->value => PayerRates::RN,
        Disciplines::COVERAGE_RN_TO_LVN_LPN->value => PayerRates::RN,
        Disciplines::COVERAGE_RN_TO_LVN_LPN_MDCP->value => PayerRates::RN,

        Disciplines::RESPITE_RN->value => PayerRates::RN,
        Disciplines::RESPITE_RN_MDCP->value => PayerRates::RN,
        Disciplines::RESPITE_RN_TO_LVN->value => PayerRates::RN,
        Disciplines::RESPITE_RN_TO_LVN_MDCP->value => PayerRates::RN,
        Disciplines::RESPITE_COVERAGE_RN->value => PayerRates::RN,
        Disciplines::RESPITE_COVERAGE_RN_MDCP->value => PayerRates::RN,
        Disciplines::RESPITE_COVERAGE_RN_TO_LVN->value => PayerRates::RN,

        //RN_SPECIAL
        Disciplines::RN_SPECIALIZED->value => PayerRates::RN_SPECIAL,
        Disciplines::RN_SPECIALIZED_MDCP->value => PayerRates::RN_SPECIAL,
        Disciplines::RN_TO_LVN_SPECIALIZED->value => PayerRates::RN_SPECIAL,
        Disciplines::RN_TO_LVN_SPECIALIZED_MDCP->value => PayerRates::RN_SPECIAL,
        Disciplines::COVERAGE_RN_SPECIALIZED->value => PayerRates::RN_SPECIAL,
        Disciplines::COVERAGE_RN_SPECIALIZED_MDCP->value => PayerRates::RN_SPECIAL,
        Disciplines::COVERAGE_RN_TO_LVN_SPECIALIZED_MDCP->value => PayerRates::RN_SPECIAL,
        Disciplines::COVERAGE_RN_TO_LVN_SPECIALIZED->value => PayerRates::RN_SPECIAL,

        Disciplines::RESPITE_RN_SPECIALIZED->value => PayerRates::RN_SPECIAL,
        Disciplines::RESPITE_RN_SPECIALIZED_MDCP->value => PayerRates::RN_SPECIAL,
        Disciplines::RESPITE_RN_TO_LVN_SPECIALIZED->value => PayerRates::RN_SPECIAL,
        Disciplines::RESPITE_RN_TO_LVN_SPECIALIZED_MDCP->value => PayerRates::RN_SPECIAL,
        Disciplines::RESPITE_COVERAGE_RN_SPECIALIZED->value => PayerRates::RN_SPECIAL,
        Disciplines::RESPITE_COVERAGE_RN_SPECIALIZED_MDCP->value => PayerRates::RN_SPECIAL,
        Disciplines::RESPITE_COVERAGE_RN_TO_LVN_SPECIALIZED_MDCP->value => PayerRates::RN_SPECIAL,
        Disciplines::RESPITE_COVERAGE_RN_TO_LVN_SPECIALIZED->value => PayerRates::RN_SPECIAL,
    ];

    /**
     * Accepts either the raw discipline string OR the enum.
     */
    public static function rateFor(string|Disciplines $discipline): ?PayerRates
    {
        $key = $discipline instanceof Disciplines ? $discipline->value : $discipline;

        return self::MAP[$key] ?? null;
    }

    public static function has(string|Disciplines $discipline): bool
    {
        return self::rateFor($discipline) !== null;
    }
}
