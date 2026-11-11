<?php

namespace Tests\Support\Fixtures;

class AocDocumentsFixture extends Fixture
{
    protected function table(): string
    {
        return 'aoc_documents';
    }

    protected function defaults(): array
    {
        return [
            'entity_type' => 'provider',
            'created_at' => now(),
        ];
    }
}
