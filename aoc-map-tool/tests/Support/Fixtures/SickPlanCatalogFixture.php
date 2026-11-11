<?php

namespace Tests\Support\Fixtures;

use Illuminate\Support\Str;

class SickPlanCatalogFixture extends Fixture
{
    protected function table(): string
    {
        return 'sick_plan_catalog';
    }

    protected function defaults(): array
    {
        return [
            'domain' => 'Hydration',
            'activity_code' => 'ACT_' . Str::upper(Str::random(8)),
            'description' => 'Sick plan catalog item',
        ];
    }
}
