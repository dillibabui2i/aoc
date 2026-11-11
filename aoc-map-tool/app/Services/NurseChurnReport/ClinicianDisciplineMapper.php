<?php

namespace App\Services\NurseChurnReport;

use Illuminate\Support\Carbon;
use Throwable;

final class ClinicianDisciplineMapper
{
    private const FIELDS = [
        'Application Date',
        'Hired Date',
    ];

    public function map(?string $raw): string
    {
        if (!$raw) {
            return 'N/A';
        }

        $skills = array_map('trim', explode(',', $raw));

        $disciplines = [];

        foreach ($skills as $skill) {
            if (in_array($skill, ['RN', 'LVN', 'LPN'])) {
                $disciplines[] = 'PDN';
            } elseif (in_array($skill, ['CNA', 'PNA', 'LHA'])) {
                $disciplines[] = 'CNA';
            } elseif ($skill === 'HHA') {
                $disciplines[] = 'HHA';
            }
        }

        return $disciplines
            ? implode(', ', array_unique($disciplines))
            : 'N/A';
    }

    public function mapDiscipline(array $result): void
    {
        foreach ($result as $row) {
            if (property_exists($row, 'Clinician Discipline')) {
                $row->{'Clinician Discipline'} =
                    $this->map($row->{'Clinician Discipline'});
            }
        }
    }

    public function applySearchForMappedDiscipline(array &$rowFilter, array &$where): void
    {
        if (empty($rowFilter['Clinician Discipline'])) {
            return;
        }

        $discipline = strtoupper(trim($rowFilter['Clinician Discipline']));

        match ($discipline) {
            'PDN' => $where[] = "mssql_providers.DisciplineName IN ('RN','LVN','LPN')",
            'CNA' => $where[] = "mssql_providers.DisciplineName IN ('CNA','PNA','LHA')",
            'HHA' => $where[] = "mssql_providers.DisciplineName = 'HHA'",
            default => null,
        };

        unset($rowFilter['Clinician Discipline']);
    }

    public function formatDateFields(array $rows): void
    {
        foreach ($rows as $row) {
            foreach (self::FIELDS as $field) {
                if (
                    property_exists($row, $field)
                    && !empty($row->{$field})
                ) {
                    $row->{$field} = $this->format($row->{$field});
                }
            }
        }
    }

    private function format(string $value): string
    {
        try {
            return Carbon::parse($value)->format('m/d/Y');
        } catch (Throwable) {
            logger()->warning('Invalid date format in Nurse Churn report', [
                'value' => $value,
            ]);

            return $value;
        }
    }
}
