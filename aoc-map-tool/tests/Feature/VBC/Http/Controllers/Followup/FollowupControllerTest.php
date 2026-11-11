<?php

namespace Tests\Feature\VBC\Http\Controllers\Followup;

use App\VBC\Http\Controllers\Followup\FollowupController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FollowupControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testExample(): void
    {
        $sut = $this->app->make(FollowupController::class);

        $this->markTestIncomplete('TODO: replace with a real assertion against aoc_test');
    }
}
