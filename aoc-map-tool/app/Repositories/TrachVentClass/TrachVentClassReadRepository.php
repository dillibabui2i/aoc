<?php

namespace App\Repositories\TrachVentClass;

use App\TrachVentClass;
use App\TrachVentClassOccurrence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TrachVentClassReadRepository
{
    public function __construct(
        private TrachVentClass $trachVentClass,
        private TrachVentClassOccurrence $trachVentClassOccurrence,
    ) {
    }

    public function forGridQuery(?string $searchValue = null): Builder
    {
        $query = $this->trachVentClass->newQuery()
            ->select('trach_vent_classes.*', 'class_categories.name as class_category_name')
            ->withCount('occurrences')
            ->join('class_categories', 'class_categories.id', '=', 'trach_vent_classes.type');

        if ($searchValue !== null && $searchValue !== '') {
            $searchValue = '%' . $searchValue . '%';
            $query->where(function ($query) use ($searchValue) {
                $query->where('class_categories.name', 'like', $searchValue)
                    ->orWhere('trach_vent_classes.class_date', 'like', $searchValue);
            });
        }

        return $query;
    }

    public function getInstructorFutureClasses(int $userId): Collection
    {
        return $this->trachVentClassOccurrence
            ->join('trach_vent_classes', 'trach_vent_classes.id', '=', 'trach_vent_class_occurrences.class_id')
            ->select('trach_vent_class_occurrences.*')
            ->where(function ($query) use ($userId) {
                $query->where('trach_vent_class_occurrences.instructor_id', $userId)
                    ->orWhere(function ($query) use ($userId) {
                        $query->whereNull('trach_vent_class_occurrences.instructor_id')
                            ->where('trach_vent_classes.instructor_id', $userId);
                    });
            })
            ->whereRaw(
                "STR_TO_DATE(CONCAT(`trach_vent_class_occurrences`.`class_date`, ' ', COALESCE(`trach_vent_class_occurrences`.`class_time`, `trach_vent_classes`.`class_time`)), '%Y-%m-%d %H:%i') > NOW()"
            )
            ->get();
    }

    public function find(string $id): ?TrachVentClass
    {
        return $this->trachVentClass->find($id);
    }
}
