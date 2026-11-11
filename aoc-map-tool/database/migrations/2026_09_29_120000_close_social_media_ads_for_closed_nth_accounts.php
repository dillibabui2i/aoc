<?php

use App\SocialMedia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        SocialMedia::where('completed', 0)
            ->where('is_deleted', 0)
            ->where(fn ($query) => $query->where('ad_placed', 0)->orWhereNull('ad_placed'))
            ->whereIn('aoc_nthaccounts_id', function ($query) {
                $query->select('id')->from('NeedToHire_Accounts')->where('IsActive', 0);
            })
            ->update([
                'completed' => 1,
                'ads_close_reason' => config('constant.socialMediaAutoCloseReason'),
                'updated_at' => DB::raw('NOW()'),
            ]);
    }

    public function down(): void
    {

    }
};
