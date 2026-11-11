<?php

namespace App\Events\Adjustment;

use App\Enums\JobCategoryAction;
use App\Models\Adjustment;
use App\Models\JobCategories;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdjustmentCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Adjustment $adjustment,
        public ?int $userId = null,
    )
    {}
}
