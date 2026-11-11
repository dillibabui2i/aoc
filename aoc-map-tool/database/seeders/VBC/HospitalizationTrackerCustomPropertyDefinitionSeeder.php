<?php

namespace Database\Seeders\VBC;

use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerCustomPropertyDefinitionModel;
use Illuminate\Database\Seeder;

class HospitalizationTrackerCustomPropertyDefinitionSeeder extends Seeder
{
    private const DEFINITIONS = [
        ['key' => 'was_tt_contacted_prior_to_admit_or_transfer', 'name' => 'Was TT Contacted Prior to Admit or Transfer', 'default_value' => false],
        ['key' => 'family_contact_date_by_icm_or_tt', 'name' => 'Family Contact Date by ICM or TT', 'default_value' => null],
        ['key' => 'resumption_of_care_date', 'name' => 'Resumption of Care Date', 'default_value' => null],
        ['key' => 'thirty_day_outcome', 'name' => '30 Day Outcome', 'default_value' => null],
        ['key' => 'first_billable_shift_completed_within_48_hours', 'name' => 'First Billable Shift Completed Within 48 Hours', 'default_value' => false],
        ['key' => 'first_billable_shift_after_discharge_date', 'name' => 'First Billable Shift After Discharge Date', 'default_value' => null],
        ['key' => 'incident_report_completed_in_kt', 'name' => 'Incident Report Completed in KT', 'default_value' => false],
        ['key' => 'staffing_percentage_during_week_of_ed_visit', 'name' => 'Staffing Percentage During Week of ED Visit', 'default_value' => null],
        ['key' => 'was_pdn_on_shift_during_ed_visit', 'name' => 'Was PDN on Shift During ED Visit', 'default_value' => false],
        ['key' => 'was_tt_contacted_for_ed_visit', 'name' => 'Was TT Contacted for ED Visit', 'default_value' => false],
        ['key' => 'did_tt_direct_ed_visit', 'name' => 'Did TT Direct ED Visit', 'default_value' => false],
        ['key' => 'staffing_concerns', 'name' => 'Staffing Concerns', 'default_value' => null],
        ['key' => 'dme_concerns', 'name' => 'DME Concerns', 'default_value' => null],
        ['key' => 'incident_report_filed_at', 'name' => 'Incident Report Filed At', 'default_value' => null],
        ['key' => 'incident_occurred_at', 'name' => 'Incident Occurred At', 'default_value' => null],
        ['key' => 'tt_first_outreach_at', 'name' => 'TT First Outreach At', 'default_value' => null],
        ['key' => 'was_diagnosis_made', 'name' => 'Was the Diagnosis Made?', 'default_value' => false],
        ['key' => 'staff_family_training_required', 'name' => 'Staff / Family Training Required?', 'default_value' => false],
        // PD-3983 ED Visit Info tab fields not covered above.
        ['key' => 'sent_2067_form_to_payer', 'name' => 'Was a 2067 Form Sent to Payer', 'default_value' => false],
        ['key' => 'annual_flu_vaccine_status', 'name' => 'Annual Flu Vaccine Status', 'default_value' => null],
        ['key' => 'flu_vaccination_date', 'name' => 'Flu Vaccination Date', 'default_value' => null],
        ['key' => 'covid19_vaccine_status', 'name' => 'COVID-19 Vaccine Status', 'default_value' => null],
        ['key' => 'covid19_vaccination_date', 'name' => 'COVID-19 Vaccination Date', 'default_value' => null],
        ['key' => 'rsv_vaccine_status', 'name' => 'RSV Vaccine Status', 'default_value' => null],
        ['key' => 'rsv_vaccination_date', 'name' => 'RSV Vaccination Date', 'default_value' => null],
    ];

    public function run(): void
    {
        foreach (self::DEFINITIONS as $definition) {
            HospitalizationTrackerCustomPropertyDefinitionModel::create($definition);
        }
    }
}
