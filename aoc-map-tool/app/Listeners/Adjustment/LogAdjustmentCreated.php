<?php

namespace App\Listeners\Adjustment;

use App\Events\Adjustment\AdjustmentChanged;
use App\Events\Adjustment\AdjustmentCreated;
use App\Notes;
use App\User;

class LogAdjustmentCreated
{
    public const APP_BOT_USER_ID = 1;

    public function handle(AdjustmentCreated $event): void
    {
        $user = User::query()->find($event->userId ?? self::APP_BOT_USER_ID);
        $adjustment = $event->adjustment->load(['level_1:id,name', 'level_2:id,name', 'level_3:id,name']);

        $note = sprintf(
            "%s added Adjustment Category \"%s\".\n\n".
            "Approval Level 1: %s\n".
            "Approval Level 2: %s\n".
            "Approval Level 3: %s\n".
            "Requires Ops Review?: %s",
            $user->name,
            $adjustment->category,
            $adjustment->level_1_display_name ?? 'None',
            $adjustment->level_2_display_name ?? 'None',
            $adjustment->level_3_display_name ?? 'None',
            $adjustment->requires_ops_review ? 'Yes' : 'No',
        );


        Notes::query()->create([
            'type' => 'edit',
            'entity' => 'adjustment-change',
            'entity_id' => $adjustment->id,
            'notes' => $note,
            'created_by' => $user->id,
            'completed' => 1,
        ]);
    }
}
