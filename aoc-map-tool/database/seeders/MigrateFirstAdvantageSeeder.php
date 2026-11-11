<?php

namespace Database\Seeders;

use App\FirstAdvantageCheck;
use App\RecruitingSchedule;
use DB;
use Illuminate\Database\Seeder;

class MigrateFirstAdvantageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (FirstAdvantageCheck::all() as $item) {
            $schedule = RecruitingSchedule::find($item->aoc_candidate_id);

            $type = 'provider';
            $id = $schedule->aoc_provider_id;

            if (!empty($schedule->aoc_candidate_id)) {
                $type = 'candidate';
                $id = $schedule->aoc_candidate_id;
            }

            $item->type = $type;
            $item->aoc_candidate_id = $id;
            $item->save();
        }
    }
}
