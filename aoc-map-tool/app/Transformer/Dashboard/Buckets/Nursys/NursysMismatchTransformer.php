<?php

namespace App\Transformer\Dashboard\Buckets\Nursys;

use App\Helpers\H;
use App\Renewables;
use App\Services\Dashboard\Buckets\Nursys\NursysMismatchOverdueService;
use App\Traits\DateFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class NursysMismatchTransformer
{
    use DateFormatter;

    private const OVERDUE_ROW_CLASS = 'dashboard-older-record';

    public function __construct(private NursysMismatchOverdueService $overdueService)
    {
    }

    public function transform(Collection $data): JsonResponse
    {
        return DataTables::of($data)
            ->setRowAttr(['class' => fn($row) => $this->getRowClass($row)])
            ->editColumn('expiration_date', fn($row) => $this->formatExpirationDate($row))
            ->editColumn('nursys_mismatch_created_at', fn($row) => $this->formatCreatedDate($row->nursys_mismatch_created_at))
            ->addIndexColumn()
            ->escapeColumns('Action')
            ->make(true);
    }

    public function transformShowAllRows(Collection $rows): Collection
    {
        foreach ($rows as $row) {
            $createdAt = $row->nursys_mismatch_created_at;
            $row->is_overdue = $this->overdueService->isOverdue($createdAt);
            $row->nursys_mismatch_created_at = $this->formatCreatedDate($createdAt);
        }

        return $rows;
    }

    public function formatCreatedDate($createdAt): string
    {
        return empty($createdAt) ? '-' : $this->getFormatedLongDateTime($createdAt);
    }

    private function getRowClass($row): string
    {
        return $this->overdueService->isOverdue($row->nursys_mismatch_created_at)
            ? self::OVERDUE_ROW_CLASS
            : '';
    }

    private function formatExpirationDate($row): string
    {
        if ($row->expiration_date == '0000-00-00' || empty($row->expiration_date)) {
            return '-';
        }

        return H::getFormatedDate($row->expiration_date);
    }
}
