<?php

namespace Tests\Feature\VBC\Http\Controllers\CareCoordination;

use App\VBC\Http\Controllers\CareCoordination\CareCoordinationController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CareCoordinationControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testExample(): void
    {
        $sut = $this->app->make(CareCoordinationController::class);

        $this->markTestIncomplete('TODO: replace with a real assertion against aoc_test');
    }
}
