<?php

namespace Tests\Support\Fixtures;

class RenewablesFixture extends Fixture
{
    protected function table(): string
    {
        return 'renewables';
    }

    protected function defaults(): array
    {
        return [
            'nursys_sync_status' => 0,
        ];
    }
}
