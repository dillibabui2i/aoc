<?php

namespace Database\Seeders;

use App\Models\Adjustment;
use Illuminate\Database\Seeder;

class AdjustmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->getItems() as $item) {
            Adjustment::updateOrCreate([
                'category' => $item['category'],
            ],[
                'category' => $item['category'],
                'level_1_type' => $item['level_1_type'],
                'level_1_id' => $item['level_1_id'],
                'level_2_type' => $item['level_2_type'],
                'level_2_id' => $item['level_2_id'],
                'level_3_type' => $item['level_3_type'] ?? null,
                'level_3_id' => $item['level_3_id'] ?? null,
            ]);
        }
    }

    private function getItems() : array
    {
        return [
            [
                'category' => 'Compliance',
                'level_1_type' => 'user',
                'level_1_id' => 46,
                'level_2_type' => 'user',
                'level_2_id' => 1330,
            ],
            [
                'category' => 'Contract Issue',
                'level_1_type' => 'user',
                'level_1_id' => 1346,
                'level_2_type' => 'user',
                'level_2_id' => 1330,
            ],
            [
                'category' => 'Eligibility Issue',
                'level_1_type' => 'user',
                'level_1_id' => 1369,
                'level_2_type' => 'user',
                'level_2_id' => 1108,
                'level_3_type' => 'user',
                'level_3_id' => 1330,
            ],
            [
                'category' => 'No Auth SOC',
                'level_1_type' => 'user',
                'level_1_id' => 1369,
                'level_2_type' => 'user',
                'level_2_id' => 1108,
                'level_3_type' => 'user',
                'level_3_id' => 1330,
            ],
            [
                'category' => 'No Authorization',
                'level_1_type' => 'user',
                'level_1_id' => 1369,
                'level_2_type' => 'user',
                'level_2_id' => 1108,
                'level_3_type' => 'user',
                'level_3_id' => 1330,
            ],
            [
                'category' => 'Overutilized Authorization',
                'level_1_type' => 'user',
                'level_1_id' => 1369,
                'level_2_type' => 'user',
                'level_2_id' => 1108,
                'level_3_type' => 'user',
                'level_3_id' => 1330,
            ],
            [
                'category' => 'Patient Bad Debt',
                'level_1_type' => 'role',
                'level_1_id' => 57,
                'level_2_type' => 'user',
                'level_2_id' => 1330,
            ],
            [
                'category' => 'Timely Follow Up – Ops',
                'level_1_type' => 'role',
                'level_1_id' => 57,
                'level_2_type' => 'user',
                'level_2_id' => 1330,
            ],
            [
                'category' => 'Timely Follow Up – RCM',
                'level_1_type' => 'role',
                'level_1_id' => 57,
                'level_2_type' => 'user',
                'level_2_id' => 1330,
            ],
        ];
    }
}
