<?php

namespace Tests\Support\Fixtures;

use Illuminate\Support\Str;

class CareCoordinationTypeFixture extends Fixture
{
    protected function table(): string
    {
        return 'care_coordination_types';
    }

    protected function defaults(): array
    {
        return [
            'key' => 'type_' . Str::lower(Str::random(8)),
            'name' => 'Care Coordination Type',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
