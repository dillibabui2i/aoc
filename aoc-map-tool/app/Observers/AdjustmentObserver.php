<?php

namespace App\Observers;

use App\Events\Adjustment\AdjustmentCreated;
use App\Models\Adjustment;
use Illuminate\Support\Facades\Auth;

class AdjustmentObserver
{
    /**
     * @param Adjustment $adjustment
     * @return void
     */
    public function created(Adjustment $adjustment): void
    {
        $userId = Auth::id();
        event(new AdjustmentCreated(adjustment : $adjustment, userId: $userId));
    }
}
