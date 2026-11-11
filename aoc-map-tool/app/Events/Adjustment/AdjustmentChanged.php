<?php

namespace App\Events\Adjustment;

use App\Enums\JobCategoryAction;
use App\Models\Adjustment;
use App\Models\JobCategories;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdjustmentChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public array $oldData,
        public array $newData,
        public ?int $userId = null,
    )
    {}
}
