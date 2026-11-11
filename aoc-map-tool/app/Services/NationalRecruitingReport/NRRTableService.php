<?php

namespace App\Services\NationalRecruitingReport;

use App\Enums\NRR\NRRTypes;

class NRRTableService
{
    private const COLUMN_INDEX_ID = 2;

    /**
     * @param string $source
     * @return array[]
     */
    public function buildTableColumns(string $source): array
    {
        $excludedBySource = [
            NRRTypes::BRANCH->value,
            NRRTypes::APPROVER->value,
        ];

        $baseColumns = [
            [
                'field' => 'state',
                'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'whiteboard_row',
                'heading' => ['title' => 'Service', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'region',
                'heading' => ['title' => 'Region', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'territory',
                'heading' => ['title' => 'Territory', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'hire_date',
                'heading' => ['title' => 'Hire Date', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => 'date', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ]
        ];

        $columnBySource = match ($source) {
            'recruiter' => [
                [
                    'field' => 'Recruiter',
                    'heading' => ['title' => 'Recruiter', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
            ],
            'approver' => [
                [
                    'field' => 'approver',
                    'heading' => ['title' => 'Approver', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
            ],
            default => [
                [
                    'field' => 'Branch_Location',
                    'heading' => ['title' => 'Branch', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ]
            ],
        };

        if (in_array($source, $excludedBySource)) {
            $fieldsToRemove = ['territory', 'hire_date'];

            $baseColumns = array_values(
                array_filter($baseColumns, fn($item) =>
                !in_array($item['field'], $fieldsToRemove, true)
                )
            );
        }

        foreach ($baseColumns as $index => $item) {
            if ($index === self::COLUMN_INDEX_ID) {
                array_splice($baseColumns, $index + 1, 0, $columnBySource);
                break;
            }
        }

        return $baseColumns;
    }
}
