<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $record = DB::table('map_configurations')
            ->where('map_tablename', 'maps_nthalerts')
            ->first();

        if ($record && isset($record->fields_datatable)) {
            $json = json_decode($record->fields_datatable, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                $newHeadings = ["Sms status", "Preference status", "Subscription status"];
                $newData = ["aoc_cf_sms_status", "aoc_cf_preference_status", "aoc_cf_subscription_status"];

                foreach ($newHeadings as $heading) {
                    if (!in_array($heading, $json['table_heading'])) {
                        $json['table_heading'][] = $heading;
                    }
                }

                foreach ($newData as $dataKey) {
                    if (!in_array($dataKey, $json['datatable'])) {
                        $json['datatable'][] = $dataKey;
                    }
                }

                DB::table('map_configurations')
                    ->where('map_tablename', 'maps_nthalerts')
                    ->update(['fields_datatable' => json_encode($json)]);
            }
        }
    }

    public function down(): void
    {
        $record = DB::table('map_configurations')
            ->where('map_tablename', 'maps_nthalerts')
            ->first();

        if ($record && isset($record->fields_datatable)) {
            $json = json_decode($record->fields_datatable, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                $removeHeadings = ["Sms status", "preference status", "subscription status"];
                $removeData = ["aoc_cf_sms_status", "aoc_cf_preference_status", "aoc_cf_subscription_status"];

                $json['table_heading'] = array_values(array_diff($json['table_heading'], $removeHeadings));
                $json['datatable'] = array_values(array_diff($json['datatable'], $removeData));

                DB::table('map_configurations')
                    ->where('map_tablename', 'maps_nthalerts')
                    ->update(['fields_datatable' => json_encode($json)]);
            }
        }
    }
};

