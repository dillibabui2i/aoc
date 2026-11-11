<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const OLD_CATEGORY = 'GA: CNA/HHA';
    private const NEW_CATEGORY = 'GA: Certified Nursing and Home Health Aides (CNA/HHA)';

    public function up(): void
    {
        DB::table('job_categories')
            ->where('category', self::OLD_CATEGORY)
            ->update(['category' => self::NEW_CATEGORY]);
        DB::table('jobs')
            ->where('category', self::OLD_CATEGORY)
            ->update(['category' => self::NEW_CATEGORY]);
        DB::table('candidates')
            ->where('jobCategoryName', self::OLD_CATEGORY)
            ->update(['jobCategoryName' => self::NEW_CATEGORY]);
    }

    public function down(): void
    {
        DB::table('job_categories')
            ->where('category', self::NEW_CATEGORY)
            ->update(['category' => self::OLD_CATEGORY]);
        DB::table('jobs')
            ->where('category', self::NEW_CATEGORY)
            ->update(['category' => self::OLD_CATEGORY]);
        DB::table('candidates')
            ->where('jobCategoryName', self::NEW_CATEGORY)
            ->update(['jobCategoryName' => self::OLD_CATEGORY]);
    }
};
