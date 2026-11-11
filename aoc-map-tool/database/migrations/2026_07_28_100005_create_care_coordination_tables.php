<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TYPES = [
        'angel_link' => 'Angel Link',
        'branch_ccm' => 'Branch CCM',
        'branch_ccc' => 'Branch CCC',
        'bd' => 'BD',
        'rt' => 'RT',
        'auth' => 'AUTH',
        'team' => 'TEAM',
        'therapy' => 'THERAPY',
        'dme' => 'DME',
        'md' => 'MD',
        'pharmacy' => 'Pharmacy',
        'insurance' => 'Insurance',
        'transportation' => 'Transportation',
        'triage_team' => 'Triage Team',
        'other' => 'Other',
    ];

    public function up(): void
    {
        Schema::create('care_coordination_types', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('care_coordination_types')->insert(
            collect(self::TYPES)->map(fn (string $name, string $key) => [
                'key' => $key,
                'name' => $name,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->values()->all()
        );

        Schema::create('care_coordinations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('encounter_id');
            $table->unsignedBigInteger('care_coordination_type_id');
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('encounter_id')->references('id')->on('encounters');
            $table->foreign('care_coordination_type_id')->references('id')->on('care_coordination_types');
            $table->foreign('assigned_to_id')->references('id')->on('users');
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_coordinations');
        Schema::dropIfExists('care_coordination_types');
    }
};
