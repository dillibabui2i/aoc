<?php

namespace Tests\Support\Fixtures;

use Illuminate\Support\Str;

class ContactTypeFixture extends Fixture
{
    protected function table(): string
    {
        return 'contact_types';
    }

    protected function defaults(): array
    {
        return [
            'key' => 'contact_' . Str::lower(Str::random(8)),
            'name' => 'Contact Type',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
