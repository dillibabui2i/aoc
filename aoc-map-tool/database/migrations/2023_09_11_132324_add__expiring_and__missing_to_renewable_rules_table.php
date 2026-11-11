<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddExpiringAndMissingToRenewableRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('renewable_rules')->truncate();

        Schema::table('renewable_rules', function (Blueprint $table) {
            $table->boolean('Expiring');
            $table->boolean('Missing');
        });

        $data = [
           0 => [
                "renewable_type_id" => 13,
                "states" => 'AZ,CO,FL,NC,PA,SC,TX',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 1
           ],
           1 => [
                "renewable_type_id" => 24,
                "states" => 'AZ,CO,FL,NC,PA,SC,TX',
                "Expiring" => 0,
                "Missing" => 1,
                "email_to_nurse" => 0
            ],
           2 => [
                "renewable_type_id" => 2,
                "states" => 'AZ,CO,FL,NC,PA,TX',
                "Expiring" => 0,
                "Missing" => 1,
                "email_to_nurse" => 0
            ],
            3 => [
                "renewable_type_id" => 15,
                "states" => 'AZ',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 0
            ],
            4 => [
                "renewable_type_id" => 16,
                "states" => 'AZ',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 1
            ],
            5 => [
                "renewable_type_id" => 17,
                "states" => 'AZ',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 1
            ],
            6 => [
                "renewable_type_id" => 30,
                "states" => 'CO',
                "Expiring" => 0,
                "Missing" => 1,
                "email_to_nurse" => 0
            ],
            7 => [
                "renewable_type_id" => 28,
                "states" => 'CO',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 1
            ],
            8 => [
                "renewable_type_id" => 11,
                "states" => 'CO',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 1
            ],
            9 => [
                "renewable_type_id" => 12,
                "states" => 'CO',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 1
            ],
            10 => [
                "renewable_type_id" => 4,
                "states" => 'AZ,CO,FL,NC,PA,SC,TX',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 1
            ],
            11 => [
                "renewable_type_id" => 3,
                "states" => 'FL,PA',
                "Expiring" => 0,
                "Missing" => 1,
                "email_to_nurse" => 0
            ],
            12 => [
                "renewable_type_id" => 3,
                "states" => 'NC',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 0
            ],
            13 => [
                "renewable_type_id" => 8,
                "states" => 'AZ',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 1
            ],
            14 => [
                "renewable_type_id" => 8,
                "states" => 'TX',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 1
            ],
            15 => [
                "renewable_type_id" => 7,
                "states" => 'AZ,CO,FL,NC,PA,SC,TX',
                "Expiring" => 0,
                "Missing" => 1,
                "email_to_nurse" => 0
            ],
            16 => [
                "renewable_type_id" => 29,
                "states" => 'AZ,CO,FL,NC,PA,SC,TX',
                "Expiring" => 0,
                "Missing" => 1,
                "email_to_nurse" => 0
            ],
            17 => [
                "renewable_type_id" => 21,
                "states" => 'NC',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 0
            ],
            18 => [
                "renewable_type_id" => 20,
                "states" => 'NC',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 1
            ],
            19 => [
                "renewable_type_id" => 22,
                "states" => 'NC',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 1
            ],
            20 => [
                "renewable_type_id" => 9,
                "states" => 'AZ,CO,FL,NC,PA,SC,TX',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 1
            ],
            21 => [
                "renewable_type_id" => 10,
                "states" => 'AZ,CO,FL,NC,PA,TX',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 1
            ],
            22 => [
                "renewable_type_id" => 6,
                "states" => 'TX',
                "Expiring" => 1,
                "Missing" => 1,
                "email_to_nurse" => 0
            ],
            23 => [
                "renewable_type_id" => 14,
                "states" => 'TX',
                "Expiring" => 0,
                "Missing" => 1,
                "email_to_nurse" => 0
            ],
        ];
        DB::table('renewable_rules')->insert($data);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renewable_rules', function (Blueprint $table) {
            $table->dropColumn('Expiring');
            $table->dropColumn('Missing');
        });
    }
}
