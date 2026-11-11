<?php

namespace Tests\Support\Fixtures;

class MssqlEmploymentStatusFixture extends Fixture
{
    protected function table(): string
    {
        return 'mssql_employmentstatus';
    }

    protected function defaults(): array
    {
        return [
            'EmploymentStatusID' => random_int(10000, 99999),
            'EmploymentStatus' => 'Active',
            'is_deleted' => 0,
        ];
    }
}
