<?php

namespace App\Mixins;

use App\Enums\DeletionStatus;
use Illuminate\Database\Query\Builder;

final class SoftDeleteQueryBuilderMixin
{
    public function softDelete(): \Closure
    {
        return function (?string $table = null): int {
            /** @var Builder $this */

            $table = ($table ?? $this->from) . '.';

            return $this->update([
                $table . 'is_deleted' => DeletionStatus::DELETED->value,
                $table . 'deleted_at' => now(),
            ]);
        };
    }

    public function active(): \Closure
    {
        return function (?string $table = null): Builder {
            /** @var Builder $this */

            $table = ($table ?? $this->from) . '.';

            return $this->where($table . 'is_deleted', DeletionStatus::ACTIVE->value);
        };
    }
}
