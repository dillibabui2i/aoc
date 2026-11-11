<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClinicianEnrollmentFieldsToTrachVentClassRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('trach_vent_class_request', 'tracker_id')) {
            Schema::table('trach_vent_class_request', function (Blueprint $table) {
                $table->dropColumn('tracker_id');
            });
        }

        Schema::table('trach_vent_class_request', function (Blueprint $table) {
            $table->integer('provider_id')->nullable()->after('trach_vent_class_id');
            $table->integer('candidate_id')->nullable()->after('provider_id');
            $table->integer('client_id')->nullable()->after('candidate_id');
            $table->string('discipline_snapshot', 100)->nullable()->after('client_id');
            $table->string('phone_number_snapshot', 20)->nullable()->after('discipline_snapshot');
            $table->date('start_date')->nullable()->after('phone_number_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trach_vent_class_request', function (Blueprint $table) {
            $table->dropColumn(['provider_id', 'candidate_id', 'client_id', 'discipline_snapshot', 'phone_number_snapshot', 'start_date']);
        });

        if (!Schema::hasColumn('trach_vent_class_request', 'tracker_id')) {
            Schema::table('trach_vent_class_request', function (Blueprint $table) {
                $table->integer('tracker_id')->after('trach_vent_class_id')->comment('primary key of mssql_recruitingtracker');
            });
        }
    }
}
