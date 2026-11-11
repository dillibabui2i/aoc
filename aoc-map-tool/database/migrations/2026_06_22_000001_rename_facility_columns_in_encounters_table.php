<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropForeign('encounters_facility_id_foreign');
            $table->dropForeign('encounters_facility_transfer_id_foreign');
        });
        Schema::table('encounters', function (Blueprint $table) {
            $table->renameColumn('facility_id', 'hospital_id');
            $table->renameColumn('facility_transfer_id', 'hospital_transfer_id');
        });
        DB::statement('ALTER TABLE encounters MODIFY hospital_id INT UNSIGNED NULL');
        DB::statement('ALTER TABLE encounters MODIFY hospital_transfer_id INT UNSIGNED NULL');
        Schema::table('encounters', function (Blueprint $table) {
            $table->foreign('hospital_id')
                ->references('id')
                ->on('referralsource_master');
            $table->foreign('hospital_transfer_id')
                ->references('id')
                ->on('referralsource_master');
        });
    }

    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropForeign(['hospital_id']);
            $table->dropForeign(['hospital_transfer_id']);
        });
        Schema::table('encounters', function (Blueprint $table) {
            $table->renameColumn('hospital_id', 'facility_id');
            $table->renameColumn('hospital_transfer_id', 'facility_transfer_id');
        });
        DB::statement('ALTER TABLE encounters MODIFY facility_id BIGINT UNSIGNED');
        DB::statement('ALTER TABLE encounters MODIFY facility_transfer_id BIGINT UNSIGNED NULL');
        Schema::table('encounters', function (Blueprint $table) {
            $table->foreign('facility_id')
                ->references('id')
                ->on('mssql_facilities');
            $table->foreign('facility_transfer_id')
                ->references('id')
                ->on('mssql_facilities');
        });
    }
};
