<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const REASONS = [
        'Allergy Shot Reaction',
        'Animal Bite on Antibiotic Follow-up Call',
        'Ankle Injury',
        'Abortion – Spontaneous Miscarriage Follow-up Call',
        'Abortion – Surgical Procedure Follow-up Call',
        'Caterpillar Sting',
        'Central Venous Catheter – Port Symptoms and Questions',
        'Dead Animal Exposure',
        'E-Cigarette (Vaping) Nicotine – Symptoms or Questions',
        'Fingernail Infection',
        'Foot Injury',
        'Groin Injury',
        'Groin Pain or Swelling',
        'Hand Injury',
        'Hand Pain',
        'Heart Failure on Treatment Follow-up Call',
        'Home Health – Equipment or Supply Request',
        'Hospice – Diarrhea',
        'Hospice – Fever',
        'Infection Exposure Questions',
        'Leech Bite',
        'Medication Injection – Local Reactions',
        'Oxygen Therapy Questions',
        'Pregnancy – Infection Exposure Questions',
        'Post-Op Total Hip Replacement Follow-up Call',
        'Post-Op Total Knee Replacement Follow-up Call',
        'Sickle Cell Disease – Acute Pain Episodes',
        'Smoking Tobacco – Symptoms or Questions',
        'Wrist Injury'
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (self::REASONS as $reason) {
            DB::table('reasons')->insert(['reason' => $reason]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('reasons')->whereIn('reason', self::REASONS)->delete();
    }
};
