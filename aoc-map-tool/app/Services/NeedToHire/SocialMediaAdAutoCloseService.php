<?php

namespace App\Services\NeedToHire;

use App\NeedToHireChecklist;
use App\Notes;
use App\SocialMedia;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class SocialMediaAdAutoCloseService
{
    public function __construct(
        private readonly SocialMedia $socialMedia,
        private readonly Notes $notes,
        private readonly NeedToHireChecklist $needToHireChecklist
    ) {}

    public function closeOpenAdsForNth(int $aoc_nthaccounts_id, bool $postNote = true): int
    {
        return $this->closeOpenAdsForNths([$aoc_nthaccounts_id], $postNote);
    }

    /**
     * Closes open social media ads of the given (just closed) NTH accounts.
     * Works without a logged-in user (sync processes, console commands): notes are written as the system user.
     */
    public function closeOpenAdsForNths(array $aoc_nthaccounts_ids, bool $postNote = true): int
    {
        $aoc_nthaccounts_ids = array_values(array_unique(array_filter(array_map('intval', $aoc_nthaccounts_ids))));

        if (empty($aoc_nthaccounts_ids)) {
            return 0;
        }

        $openAds = $this->socialMedia
            ->query()
            ->whereIn('aoc_nthaccounts_id', $aoc_nthaccounts_ids)
            ->where('completed', 0)
            ->where('is_deleted', 0)
            ->where(fn ($query) => $query->where('ad_placed', 0)->orWhereNull('ad_placed'))
            ->get(['id', 'aoc_nthaccounts_id']);

        if ($openAds->isEmpty()) {
            return 0;
        }

        $this->socialMedia
            ->query()
            ->whereIn('id', $openAds->pluck('id'))
            ->update([
                'completed' => 1,
                'ads_close_reason' => config('constant.socialMediaAutoCloseReason'),
                'updated_at' => now(),
            ]);

        if ($postNote) {
            $this->postCloseNotes($openAds);
        }

        return $openAds->count();
    }

    private function postCloseNotes(Collection $closedAds): void
    {
        $userId = Auth::id() ?? User::SYSTEM_ID;
        $now = Carbon::now();

        $this->notes->query()->insert($closedAds->map(fn ($ad) => [
            'entity' => 'social',
            'entity_id' => $ad->id,
            'notes' => 'Social Media Ad Request was automatically closed because the associated NTH record was closed.',
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        $checklistIds = $this->needToHireChecklist
            ->query()
            ->whereIn('aoc_nthaccounts_id', $closedAds->pluck('aoc_nthaccounts_id')->unique())
            ->pluck('id', 'aoc_nthaccounts_id');

        if ($checklistIds->isEmpty()) {
            return;
        }

        $this->notes->query()->insert($checklistIds->values()->map(fn ($checklistId) => [
            'entity' => 'NeedToHire',
            'type' => 'NTH',
            'entity_id' => $checklistId,
            'notes' => 'Social Media Ad Request(s) automatically closed — associated NTH record was closed.',
            'completed' => 1,
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }
}
