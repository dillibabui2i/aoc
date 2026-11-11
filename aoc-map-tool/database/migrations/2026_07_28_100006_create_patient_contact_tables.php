<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TYPES = [
        'mother' => 'Mother',
        'father' => 'Father',
        'step_mother' => 'Step Mother',
        'step_father' => 'Step Father',
        'step_parent' => 'Step Parent',
        'guardian' => 'Guardian',
        'caregiver' => 'Caregiver',
        'sibling' => 'Sibling',
        'grandparent' => 'Grandparent',
        'aunt' => 'Aunt',
        'uncle' => 'Uncle',
        'cousin' => 'Cousin',
        'friend' => 'Friend',
        'domestic_partner' => 'Domestic Partner',
        'emergency_contact' => 'Emergency Contact',
        'next_of_kin' => 'Next of Kin',
    ];

    public function up(): void
    {
        Schema::create('contact_types', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('contact_types')->insert(
            collect(self::TYPES)->map(fn (string $name, string $key) => [
                'key' => $key,
                'name' => $name,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->values()->all()
        );

        Schema::create('patient_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('contact_type_id');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('mssql_patients');
            $table->foreign('contact_type_id')->references('id')->on('contact_types');
            $table->index('phone');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_contacts');
        Schema::dropIfExists('contact_types');
    }
};
