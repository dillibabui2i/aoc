<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table2 = [
            'map_tablename'=>'maps_nthalerts',
            'map_name'=>"NTH Alerts",
            'map_category'=>'Recruiting',
            'map_type'=>'non-PHI',
            'map_filters'=>'[]',
            'enable_address_filter'=>'0',
            'map_select_preference'=>'1',
            'map_data_preference'=>'1',
            'map_default_filter'=>'[]',
            'map_preferences'=>'1',
            'fields_on_map'=>'[]',
            'fields_datatable'=>'{"table_heading":["Distance (in Miles)"],"datatable":["distance"]}',
            'latlong_fields'=>'[]'
        ];

        DB::table("map_configurations")->insert($table2);

        Schema::create('maps_nthalerts', function (Blueprint $table) {
            $table->id();
            $table->string("ClinicialID")->nullable();
            $table->string("ProviderName")->nullable();
            
            $table->string("Discipline")->nullable();
            $table->string("MobilePhone")->nullable();
            $table->string("ExternaleEmail")->nullable();

            $table->string("ProviderStatus")->nullable();
            $table->string("Gender")->nullable();
            $table->string("WeeklyAverage")->nullable();
            $table->string("IsBilingual")->nullable();
            $table->string("HomeBranch")->nullable();

            $table->string("Address1")->nullable();
            $table->string("Address2")->nullable();
            $table->string("City")->nullable();
            $table->string("State")->nullable();
            $table->string("Zip")->nullable();

            $table->string("Language")->nullable();
            $table->string("City_Preference")->nullable();
            $table->string("State_Preference")->nullable();
            $table->string("Schedule_Preference")->nullable();
            $table->string("Skill_Preference")->nullable();

            $table->string("Environment_Preference")->nullable();
            $table->string("Lifting_Preference")->nullable();
            $table->string("Language_Preference")->nullable();
            $table->string("Subscription_Status")->nullable();
            $table->string("Preference_Status")->nullable();
            $table->string("lat")->nullable();
            $table->string("lng")->nullable();
            $table->boolean("needs_geocoding")->nullable();
            $table->boolean("is_ignored")->nullable();
            $table->boolean("is_active")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
