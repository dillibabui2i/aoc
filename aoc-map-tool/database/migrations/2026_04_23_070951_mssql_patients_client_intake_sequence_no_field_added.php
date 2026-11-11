<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            if (!Schema::hasColumn('mssql_patients', 'CLIENT_INTAKE_SEQUENCE_NO')) {
                $table->unsignedBigInteger('CLIENT_INTAKE_SEQUENCE_NO')->nullable();
                $table->index(
                    ['IntakeID', 'CLIENT_INTAKE_SEQUENCE_NO'],
                    'idx_intakeid_sequence_no'
                );
            }




        });
    }

    public function down(): void
    {
        Schema::table('mssql_patients', function (Blueprint $table) {

            if (Schema::hasColumn('mssql_patients', 'CLIENT_INTAKE_SEQUENCE_NO')) {
                $table->dropIndex('idx_intakeid_sequence_no');
                $table->dropColumn('CLIENT_INTAKE_SEQUENCE_NO');
            }
        });
    }
};
