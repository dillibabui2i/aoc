<?php

namespace Tests\Support\Fixtures;

use Illuminate\Support\Str;

class HospitalizationTrackerTrainingTypeFixture extends Fixture
{
    protected function table(): string
    {
        return 'hospitalization_tracker_training_types';
    }

    protected function defaults(): array
    {
        return [
            'key' => 'training_' . Str::lower(Str::random(8)),
            'name' => 'Training Type',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
