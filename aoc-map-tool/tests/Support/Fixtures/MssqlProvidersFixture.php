<?php

namespace Tests\Support\Fixtures;

class MssqlProvidersFixture extends Fixture
{
    protected function table(): string
    {
        return 'mssql_providers';
    }

    protected function defaults(): array
    {
        return [
            'ProviderID' => random_int(10000, 99999),
            'ClinicianID' => 'CLN-' . random_int(1000, 9999),
            'FirstName' => 'Jane',
            'LastName' => 'Doe',
            'fullName' => 'Jane Doe',
            'Search_Name' => 'Jane Doe',
            'ProviderName' => 'Doe, Jane',
            'DisciplineName' => 'RN',
            'Mobile' => '5551234567',
            'IsClinician' => 1,
            'IsOfficeStaff' => 0,
            'is_deleted' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
