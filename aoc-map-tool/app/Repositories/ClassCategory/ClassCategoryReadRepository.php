<?php

namespace App\Repositories\ClassCategory;

use App\Models\ClassCategory;
use Illuminate\Support\Collection;

class ClassCategoryReadRepository
{
    public function __construct(private ClassCategory $classCategory)
    {
    }

    public function orderedList(): Collection
    {
        return $this->classCategory->newQuery()
            ->orderByDesc('is_system')
            ->orderBy('id')
            ->get();
    }

    public function find(int $id): ?ClassCategory
    {
        return $this->classCategory->newQuery()->find($id);
    }
}
