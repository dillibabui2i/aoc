<?php

namespace Tests\Support\Fixtures;

class MssqlPatientstatusFixture extends Fixture
{
    protected function table(): string
    {
        return 'mssql_patientstatus';
    }

    protected function defaults(): array
    {
        return [
            'PatientStatusID' => random_int(100000, 999999),
            'PatientStatus' => 'Test Status',
        ];
    }
}
