<?php

namespace Tests\Support\Fixtures;

class CdcVaccineGroupsFixture extends Fixture
{
    protected function table(): string
    {
        return 'cdc_vaccine_groups';
    }

    protected function defaults(): array
    {
        return [
            'aoc_name' => 'Test Category',
            'cdc_name' => 'Test Category (CDC)',
            // unsigned SMALLINT, unique — kept away from the codes the CDC sync fills in
            'cvx_group_code' => random_int(40000, 49999),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
