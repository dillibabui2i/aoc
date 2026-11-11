<?php

namespace Tests\Support\Fixtures;

class MapsNthAlertsFixture extends Fixture
{
    protected function table(): string
    {
        return 'maps_nthalerts';
    }

    protected function defaults(): array
    {
        return [
            'SMS_Status' => '',
        ];
    }
}
