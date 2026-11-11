<?php

namespace Tests\Support\Fixtures;

class SchoolSetupRequestFixture extends Fixture
{
    protected function table(): string
    {
        return 'school_setup_requests';
    }

    protected function defaults(): array
    {
        return [
            'assigned_ccc' => 0,
            'aoc_patient_id' => 0,
            'start_date' => now()->toDateString(),
            'status' => 'Open',
            'is_deleted' => 0,
            'created_by' => 0,
            'updated_by' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
