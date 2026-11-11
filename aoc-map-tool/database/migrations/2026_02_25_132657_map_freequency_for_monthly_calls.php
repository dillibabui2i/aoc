<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            DB::statement("UPDATE mssql_patients
            SET ccc_client_call_frequency =
                CASE ccc_client_call_frequency
                    WHEN 0 THEN 0
                    WHEN 1 THEN 6
                    WHEN 2 THEN 1
                    WHEN 3 THEN 2
                    WHEN 4 THEN 6
                    WHEN 5 THEN 3
                    ELSE 0
                END"
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            // it is not possible to rollback suck migration because we are losing data during migration, so there is no rollback.
            // instead we will make a backup of the mssql_patients
        });
    }
};
