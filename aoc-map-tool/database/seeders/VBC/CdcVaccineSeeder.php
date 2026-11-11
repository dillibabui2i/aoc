<?php

namespace Database\Seeders\VBC;

use App\VBC\Models\CdcVaccine\CdcVaccineModel;
use Illuminate\Database\Seeder;

/**
 * A small sample of the CDC CVX vaccine list, enough to exercise the Immunization pages locally.
 * Reference data, so it is upserted by cvx_code rather than truncated by VBCSeeder — re-running never
 * duplicates or wipes a real CVX import. Vaccine groups are left out: cvx_group_code is nullable.
 */
class CdcVaccineSeeder extends Seeder
{
    private const VACCINES = [
        8 => 'Hepatitis B, adolescent or pediatric',
        10 => 'Poliovirus, inactivated (IPV)',
        20 => 'Diphtheria, tetanus toxoids and acellular pertussis (DTaP)',
        3 => 'Measles, mumps and rubella (MMR)',
        21 => 'Varicella',
        165 => 'Human papillomavirus 9-valent (HPV9)',
        140 => 'Influenza, seasonal, injectable, preservative free',
        208 => 'COVID-19, mRNA, LNP-S, PF, 30 mcg/0.3 mL dose',
    ];

    public function run(): void
    {
        foreach (self::VACCINES as $cvxCode => $name) {
            CdcVaccineModel::updateOrCreate(
                ['cvx_code' => $cvxCode],
                ['cdc_full_name' => $name, 'cdc_short_description' => $name, 'cdc_status' => 'Active']
            );
        }
    }
}
