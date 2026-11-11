<?php

namespace Database\Seeders\VBC;

use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerTrainingTypeModel;
use Illuminate\Database\Seeder;

/** Full list confirmed by the team lead/BA for PD-???? (Training Type dropdown). */
class HospitalizationTrackerTrainingTypeSeeder extends Seeder
{
    private const TYPES = [
        ['key' => 'abdominal_pain', 'name' => 'Abdominal Pain'],
        ['key' => 'aoc_triage_customer_service_flyer', 'name' => 'AOC Triage Customer Service Flyer'],
        ['key' => 'aspiration_precautions', 'name' => 'Aspiration Precautions'],
        ['key' => 'asthma_management', 'name' => 'Asthma Management'],
        ['key' => 'bladder_cath', 'name' => 'Bladder Cath'],
        ['key' => 'broviac_care', 'name' => 'Broviac Care'],
        ['key' => 'cellulitis', 'name' => 'Cellulitis'],
        ['key' => 'central_venous_catheter_cvc', 'name' => 'Central Venous Catheter (CVC)'],
        ['key' => 'colds', 'name' => 'Colds'],
        ['key' => 'constipation', 'name' => 'Constipation'],
        ['key' => 'cough', 'name' => 'Cough'],
        ['key' => 'cpr_protocols', 'name' => 'CPR Protocols'],
        ['key' => 'croup', 'name' => 'Croup'],
        ['key' => 'cvc', 'name' => 'CVC'],
        ['key' => 'dehydration_prevention', 'name' => 'Dehydration Prevention'],
        ['key' => 'diarrhea', 'name' => 'Diarrhea'],
        ['key' => 'earache', 'name' => 'Earache'],
        ['key' => 'emergency_department_vs_urgent_care', 'name' => 'Emergency Department vs Urgent Care'],
        ['key' => 'emergency_g_tube_replacement_protocol', 'name' => 'Emergency G-Tube Replacement Protocol'],
        ['key' => 'emergency_g_tube_to_go_bag', 'name' => 'Emergency G-Tube To Go Bag'],
        ['key' => 'emergency_trach_replacement', 'name' => 'Emergency Trach Replacement'],
        ['key' => 'emergency_trach_to_go_bag', 'name' => 'Emergency Trach To Go Bag'],
        ['key' => 'er_or_uc', 'name' => 'ER or UC'],
        ['key' => 'falls', 'name' => 'Falls'],
        ['key' => 'fever', 'name' => 'Fever'],
        ['key' => 'flu', 'name' => 'Flu'],
        ['key' => 'head_injury', 'name' => 'Head Injury'],
        ['key' => 'humidification', 'name' => 'Humidification'],
        ['key' => 'infection_control_hand_hygiene', 'name' => 'Infection Control Hand Hygiene'],
        ['key' => 'oral_and_nasal_suctioning', 'name' => 'Oral and Nasal Suctioning'],
        ['key' => 'oral_nasal_suction', 'name' => 'Oral/Nasal Suction'],
        ['key' => 'peri_care_female', 'name' => 'Peri Care Female'],
        ['key' => 'peri_care_male', 'name' => 'Peri Care Male'],
        ['key' => 'pericare', 'name' => 'Pericare'],
        ['key' => 'pneumonia', 'name' => 'Pneumonia'],
        ['key' => 'pulmonary_hygiene', 'name' => 'Pulmonary Hygiene'],
        ['key' => 'respiratory_education_for_clinical_staff', 'name' => 'Respiratory Education for clinical staff'],
        ['key' => 'rom_transfers_and_repositioning', 'name' => 'ROM, Transfers and Repositioning'],
        ['key' => 'rsv', 'name' => 'RSV'],
        ['key' => 'seasonal_illness_guide', 'name' => 'Seasonal Illness Guide'],
        ['key' => 'seizures', 'name' => 'Seizures'],
        ['key' => 'sepsis', 'name' => 'Sepsis'],
        ['key' => 'sick_plan_vb_flyer', 'name' => 'Sick Plan VB Flyer'],
        ['key' => 'trach_suctioning', 'name' => 'Trach Suctioning'],
        ['key' => 'urinary_catheterization', 'name' => 'Urinary Catheterization'],
        ['key' => 'uti', 'name' => 'UTI'],
        ['key' => 'ventricular_shunts', 'name' => 'Ventricular Shunts'],
        ['key' => 'viral_illness', 'name' => 'Viral Illness'],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $type) {
            HospitalizationTrackerTrainingTypeModel::create($type);
        }
    }
}
