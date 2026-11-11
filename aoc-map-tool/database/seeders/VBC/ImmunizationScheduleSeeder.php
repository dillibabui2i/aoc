<?php

namespace Database\Seeders\VBC;

use App\VBC\Enum\ImmunizationDoseAge\ImmunizationDoseAgeUnitEnum as Unit;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationFrequencyEnum;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationStatusEnum;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendedForEnum;
use App\VBC\Models\CdcVaccine\CdcVaccineGroupModel;
use App\VBC\Models\CdcVaccine\CdcVaccineModel;
use App\VBC\Models\ImmunizationRecommendation\ImmunizationRecommendationModel;
use App\VBC\Models\ImmunizationRecommendedFor\ImmunizationRecommendedForModel;
use Illuminate\Database\Seeder;

/**
 * Local/dev data for the Manage Immunization Schedule page: 30 vaccine templates (enough for the default
 * 10 per page to span three pages) with categories, dose age targets and a mix of states / statuses. Idempotent — re-running updates the same rows instead of duplicating them.
 * `originating_state` holds the 2-char state abbreviation. Frequency follows the number of dose targets (capped at
 * "up to 4 doses per series"; seasonal flu is annual) and Recommended for is "All Children" unless listed below —
 * both are documentation-only fields, so the values are plausible dev data, not clinical guidance.
 */
class ImmunizationScheduleSeeder extends Seeder
{
    /**
     * CVX codes already seeded by CdcVaccineSeeder (shared with the Immunization tracker seeder and the All
     * Immunizations page): their names are left alone, only the category (group) is set.
     */
    private const REFERENCE_CVX_CODES = [3, 8, 10, 20, 21, 140, 165, 208];

    private const YEARLY_CVX_CODES = [141];

    private const SHARED_DECISION_CVX_CODES = [162, 163, 207, 208];

    private const HIGH_RISK_CVX_CODES = [33, 43, 52, 62, 121, 187];

    private const GROUPS = [
        101 => 'Combination vaccine',
        102 => 'Respiratory infections',
        103 => 'Neurological infections',
        104 => 'Hepatitis infections',
        105 => 'Bacterial infections',
        106 => 'Viral infections',
    ];

    /** cvx_code => [group code, name, status, dose ages [from, from unit, to, to unit], state (default TX)] */
    private function vaccines(): array
    {
        $active = ImmunizationRecommendationStatusEnum::ACTIVE;
        $inactive = ImmunizationRecommendationStatusEnum::INACTIVE;

        // `+`, not `...`: spreading renumbers integer keys, and the keys here are CVX codes.
        return $this->coreVaccines($active, $inactive) + $this->extraVaccines($active, $inactive);
    }

    /** The rows from the Figma design. */
    private function coreVaccines(
        ImmunizationRecommendationStatusEnum $active,
        ImmunizationRecommendationStatusEnum $inactive,
    ): array {
        return [
            20 => [101, 'Diphtheria, tetanus, acellular pertussis (DTaP < 7 yrs)', $active, [
                [2, Unit::MONTH, null, null], [4, Unit::MONTH, null, null], [6, Unit::MONTH, null, null],
                [15, Unit::MONTH, 18, Unit::MONTH], [4, Unit::YEAR, 6, Unit::YEAR],
            ]],
            17 => [102, 'Haemophilus influenzae type b (Hib)', $active, [
                [2, Unit::MONTH, null, null], [4, Unit::MONTH, null, null], [6, Unit::MONTH, null, null],
                [12, Unit::MONTH, 15, Unit::MONTH],
            ]],
            10 => [103, 'Inactivated poliovirus (IPV < 18 yrs)', $active, [
                [2, Unit::MONTH, null, null], [4, Unit::MONTH, null, null], [6, Unit::MONTH, 18, Unit::MONTH],
                [4, Unit::YEAR, 6, Unit::YEAR],
            ]],
            8 => [104, 'Hepatitis B (HepB)', $active, [
                [0, null, null, null], [1, Unit::MONTH, 2, Unit::MONTH], [6, Unit::MONTH, 18, Unit::MONTH],
            ]],
            133 => [105, 'Pneumococcal conjugate (PCV15/PCV20)', $inactive, [
                [2, Unit::MONTH, null, null], [4, Unit::MONTH, null, null], [6, Unit::MONTH, null, null],
                [12, Unit::MONTH, 15, Unit::MONTH],
            ]],
            3 => [106, 'Measles, mumps, rubella (MMR)', $active, [
                [12, Unit::MONTH, 15, Unit::MONTH], [4, Unit::YEAR, 6, Unit::YEAR],
            ]],
            21 => [106, 'Varicella (VAR)', $active, [
                [12, Unit::MONTH, 15, Unit::MONTH], [4, Unit::YEAR, 6, Unit::YEAR],
            ]],
        ];
    }

    /** Filler rows so pagination (10 / 25 / 50 / 100) has something to page through. */
    private function extraVaccines(
        ImmunizationRecommendationStatusEnum $active,
        ImmunizationRecommendationStatusEnum $inactive,
    ): array {
        $m = Unit::MONTH;
        $y = Unit::YEAR;

        return [
            83 => [104, 'Hepatitis A (HepA)', $active, [[12, $m, 23, $m], [18, $m, 29, $m]], 'FL'],
            52 => [104, 'Hepatitis A, adult (HepA)', $active, [[19, $y, null, null]], 'AZ'],
            43 => [104, 'Hepatitis B, adult (HepB)', $active, [[19, $y, null, null]], 'NC'],
            116 => [106, 'Rotavirus, pentavalent (RV5)', $active, [[2, $m, null, null], [4, $m, null, null], [6, $m, null, null]], 'TX'],
            141 => [102, 'Influenza, seasonal, injectable', $active, [[6, $m, 18, $y]], null],
            208 => [102, 'COVID-19 (Pfizer-BioNTech)', $active, [[6, $m, 4, $y], [5, $y, 11, $y]], null],
            207 => [102, 'COVID-19 (Moderna)', $inactive, [[6, $m, 11, $y]], 'GA'],
            115 => [101, 'Tetanus, diphtheria, acellular pertussis (Tdap)', $active, [[11, $y, 12, $y]], 'TN'],
            113 => [101, 'Tetanus, diphtheria (Td)', $active, [[11, $y, 18, $y]], 'VA'],
            110 => [101, 'DTaP-HepB-IPV (Pediarix)', $active, [[2, $m, null, null], [4, $m, null, null], [6, $m, null, null]], 'TX'],
            130 => [101, 'DTaP-IPV (Kinrix)', $active, [[4, $y, 6, $y]], 'OK'],
            106 => [101, 'DTaP, 5 pertussis antigens', $inactive, [[2, $m, null, null], [4, $m, null, null]], 'KS'],
            165 => [106, 'Human papillomavirus, 9-valent (HPV9)', $active, [[11, $y, 12, $y], [13, $y, 26, $y]], 'TX'],
            62 => [106, 'Human papillomavirus, quadrivalent (HPV4)', $inactive, [[11, $y, 12, $y]], 'NM'],
            187 => [106, 'Zoster, recombinant', $active, [[19, $y, null, null]], 'CO'],
            121 => [106, 'Zoster, live', $inactive, [[50, $y, null, null]], 'PA'],
            94 => [106, 'Measles, mumps, rubella, varicella (MMRV)', $active, [[12, $m, 15, $m], [4, $y, 6, $y]], 'SC'],
            114 => [105, 'Meningococcal ACWY (MenACWY)', $active, [[11, $y, 12, $y], [16, $y, null, null]], 'FL'],
            162 => [105, 'Meningococcal B (Trumenba)', $active, [[16, $y, 18, $y]], 'AZ'],
            163 => [105, 'Meningococcal B (Bexsero)', $active, [[16, $y, 18, $y]], 'NC'],
            33 => [105, 'Pneumococcal polysaccharide (PPSV23)', $active, [[2, $y, 64, $y]], 'GA'],
            49 => [102, 'Haemophilus influenzae type b (Hib, PRP-OMP)', $active, [[2, $m, null, null], [4, $m, null, null], [12, $m, 15, $m]], 'TX'],
            2 => [103, 'Oral poliovirus (OPV)', $inactive, [[2, $m, null, null], [4, $m, null, null], [6, $m, 18, $m]], null],
        ];
    }

    public function run(): void
    {
        foreach (self::GROUPS as $code => $name) {
            CdcVaccineGroupModel::updateOrCreate(['cvx_group_code' => $code], ['aoc_name' => $name]);
        }

        foreach ($this->vaccines() as $cvx => $row) {
            [$groupCode, $name, $status, $doseAges] = $row;
            $state = array_key_exists(4, $row) ? $row[4] : 'TX';

            $vaccine = CdcVaccineModel::firstOrCreate(['cvx_code' => $cvx], ['cdc_full_name' => $name]);

            if (!in_array($cvx, self::REFERENCE_CVX_CODES, true) && $vaccine->cdc_full_name !== $name) {
                $vaccine->cdc_full_name = $name;
            }
            $vaccine->cvx_group_code = $groupCode;
            $vaccine->save();

            $recommendation = ImmunizationRecommendationModel::updateOrCreate(
                ['vaccine_cvx_code' => $cvx],
                [
                    'frequency' => $this->frequencyFor($cvx, count($doseAges)),
                    'originating_state' => $state,
                    'status' => $status,
                ]
            );

            ImmunizationRecommendedForModel::withTrashed()->updateOrCreate(
                ['recommendation_id' => $recommendation->id],
                ['display_name' => $this->recommendedForFor($cvx)->label(), 'deleted_at' => null]
            );

            $recommendation->doseAges()->forceDelete();
            foreach ($doseAges as [$from, $fromUnit, $to, $toUnit]) {
                $recommendation->doseAges()->create([
                    'from_age' => $from,
                    'from_age_unit' => $fromUnit,
                    'to_age' => $to,
                    'to_age_unit' => $toUnit,
                ]);
            }
        }
    }

    private function frequencyFor(int $cvx, int $doseCount): ImmunizationRecommendationFrequencyEnum
    {
        if (in_array($cvx, self::YEARLY_CVX_CODES, true)) {
            return ImmunizationRecommendationFrequencyEnum::YEARLY;
        }

        return match (true) {
            $doseCount <= 1 => ImmunizationRecommendationFrequencyEnum::ONE_PER_SERIES,
            $doseCount === 2 => ImmunizationRecommendationFrequencyEnum::TWO_PER_SERIES,
            $doseCount === 3 => ImmunizationRecommendationFrequencyEnum::THREE_PER_SERIES,
            default => ImmunizationRecommendationFrequencyEnum::FOUR_PER_SERIES,
        };
    }

    private function recommendedForFor(int $cvx): ImmunizationRecommendedForEnum
    {
        return match (true) {
            in_array($cvx, self::SHARED_DECISION_CVX_CODES, true) => ImmunizationRecommendedForEnum::SHARED_CLINICAL_DECISION_MAKING,
            in_array($cvx, self::HIGH_RISK_CVX_CODES, true) => ImmunizationRecommendedForEnum::HIGH_RISK_GROUPS,
            default => ImmunizationRecommendedForEnum::ALL_CHILDREN,
        };
    }
}
