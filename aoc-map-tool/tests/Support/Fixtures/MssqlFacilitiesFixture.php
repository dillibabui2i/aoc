<?php

namespace Tests\Support\Fixtures;

class MssqlFacilitiesFixture extends Fixture
{
    protected function table(): string
    {
        return 'mssql_facilities';
    }

    protected function defaults(): array
    {
        return [
            'State' => 'TX',
            'region' => 1,
        ];
    }
}
