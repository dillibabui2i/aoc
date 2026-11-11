<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddRenewableTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Temporary solution to unblock the users
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->listOfItems() as $item) {
            DB::table('renewables_types')->updateOrInsert(
                ['name' => $item['name']],
                [
                    'name' => $item['name'],
                    'is_deleted' => $item['is_deleted'],
                    'is_updated' => $item['is_updated'],
                    'renewable_type_id' => $item['renewable_type_id'],
                    'created_at' => $item['created_at'] ?? now(),
                    'updated_at' => now(),
                ]
            );
        }

    }

    /**
     * @return array[]
     */
    private function listOfItems(): array
    {
        return [
            [
                'name' => 'KS Health Questionnaire',
                'is_deleted' => 0,
                'is_updated' => 0,
                'renewable_type_id' => 227,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
    }
}
