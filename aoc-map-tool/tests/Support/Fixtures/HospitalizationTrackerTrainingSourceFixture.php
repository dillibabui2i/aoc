<?php

namespace Tests\Support\Fixtures;

class HospitalizationTrackerTrainingSourceFixture extends Fixture
{
    protected function table(): string
    {
        return 'hospitalization_tracker_training_sources';
    }

    protected function defaults(): array
    {
        return [
            'name' => 'Training Source',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
