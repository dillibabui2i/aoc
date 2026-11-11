<?php

namespace App\Events;

use App\Enums\JobCategoryAction;
use App\Models\JobCategories;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobCategoryChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public JobCategories $category,
        public JobCategoryAction $action,
        public ?array $oldValues = null,
        public ?array $newValues = null,
        public ?int $userId = null,
    )
    {
    }
}
