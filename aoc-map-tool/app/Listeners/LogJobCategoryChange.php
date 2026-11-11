<?php

namespace App\Listeners;

use App\Events\JobCategoryChanged;
use App\Models\JobCategoryLogs;

class LogJobCategoryChange
{
    public function handle(JobCategoryChanged $event): void
    {
        JobCategoryLogs::query()->create([
            'job_category_id' => $event->category->id,
            'action' => $event->action,
            'old_value' => $event->oldValues ? json_encode($event->oldValues) : null,
            'new_value' => $event->newValues ? json_encode($event->newValues) : null,
            'user_id' => $event->userId,
        ]);
    }
}
