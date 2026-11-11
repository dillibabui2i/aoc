<?php

namespace App\Repositories\ClassCategory;

use App\Models\ClassCategory;

class ClassCategoryWriteRepository
{
    public function __construct(private ClassCategory $classCategory)
    {
    }

    public function create(string $name, string $color): ClassCategory
    {
        return $this->classCategory->newQuery()->create([
            'name' => $name,
            'color' => $color,
            'is_system' => false,
        ]);
    }
}
