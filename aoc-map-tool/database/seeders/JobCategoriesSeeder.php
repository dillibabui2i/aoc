<?php

namespace Database\Seeders;

use App\Models\JobCategories;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JobCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $jobviteCategories = config('constant.jobviteCategories');
        $jobCategories = config('constant.jobCategories');

        DB::beginTransaction();

        try {
            JobCategories::query()->update(['is_enabled' => 0]);

            foreach ($jobviteCategories as $categoryName) {
                $exists = JobCategories::query()->where('category', $categoryName)->exists();

                if (! $exists) {
                    $details = $jobCategories[$categoryName] ?? ['label' => null, 'state' => null];

                    JobCategories::query()->create([
                        'State' => $details['state'],
                        'Type' => $details['label'],
                        'category' => $categoryName,
                        'is_enabled' => true,
                        'created_by' => null,
                        'updated_by' => null,
                    ]);
                } else {
                    JobCategories::where('category', $categoryName)->update(['is_enabled' => true]);
                }
            }

            DB::commit();

            $this->command->info('Job categories seeded: new ones added, old ones disabled.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->error('Failed to seed job categories: ' . $e->getMessage());
        }
    }
}
