<?php

declare(strict_types=1);

namespace App\Services\GpCalculator;

use App\Enums\RolesEnum;

final class GpCalculatorEscalationGuideMapper
{
    public const DEFAULT_COLUMN = 'recruiter_coverage_target';

    /**
     * @var array<string,string>
     */
    private const COLUMN_LABELS = [
        'recruiter_coverage_target' => 'Recruiter & Coverage Target',
        'recruiter_escalated' => 'Recruiter Escalated',
        'tl_manager_escalated' => 'TL/Manager Escalated',
        'tl_manager_whole_home_escalated' => 'TL/Manager Whole Home Escalated',
        'exec_team_escalated' => 'Exec Team Escalated',
        'coverage_escalated' => 'Coverage Escalated',
        'coverage_tl_manager_escalated' => 'Coverage TL/Manager Escalated',
        'coverage_tl_manager_whole_home_escalated' => 'Coverage TL/Manager Whole Home Escalated',
        'coverage_exec_team_escalated' => 'Coverage Exec Team Escalated',
    ];

    /**
     * @var array<int,list<string>>
     */
    private const ROLE_COLUMNS = [
        RolesEnum::RECRUITER->value => [
            'recruiter_coverage_target',
            'recruiter_escalated',
        ],
        RolesEnum::RECRUITING_ADMIN->value => [
            'recruiter_coverage_target',
            'recruiter_escalated',
        ],
        RolesEnum::RECRUITING_MANAGER_TEAM_LEAD->value => [
            'recruiter_coverage_target',
            'recruiter_escalated',
            'tl_manager_escalated',
            'tl_manager_whole_home_escalated',
        ],
        RolesEnum::RECRUITING_MANAGER->value => [
            'recruiter_coverage_target',
            'recruiter_escalated',
            'tl_manager_escalated',
            'tl_manager_whole_home_escalated',
        ],
        RolesEnum::COVERAGE_COORDINATOR_ID->value => [
            'recruiter_coverage_target',
            'coverage_escalated',
            'coverage_tl_manager_escalated',
        ],
        RolesEnum::MANAGER_OF_CLIENT_CARE->value => [
            'recruiter_coverage_target',
            'tl_manager_escalated',
            'tl_manager_whole_home_escalated',
            'coverage_escalated',
            'coverage_tl_manager_escalated',
            'coverage_tl_manager_whole_home_escalated',
        ],
        RolesEnum::SUPERVISOR->value => [
            'recruiter_coverage_target',
            'tl_manager_escalated',
            'tl_manager_whole_home_escalated',
            'coverage_escalated',
            'coverage_tl_manager_escalated',
            'coverage_tl_manager_whole_home_escalated',
        ],
        RolesEnum::EXECUTIVE_TEAM->value => [
            'recruiter_coverage_target',
            'recruiter_escalated',
            'tl_manager_escalated',
            'tl_manager_whole_home_escalated',
            'exec_team_escalated',
            'coverage_escalated',
            'coverage_tl_manager_escalated',
            'coverage_tl_manager_whole_home_escalated',
            'coverage_exec_team_escalated',
        ],
        RolesEnum::PRIMARY_ADMIN->value => [
            'recruiter_coverage_target',
            'recruiter_escalated',
            'tl_manager_escalated',
            'tl_manager_whole_home_escalated',
            'exec_team_escalated',
            'coverage_escalated',
            'coverage_tl_manager_escalated',
            'coverage_tl_manager_whole_home_escalated',
            'coverage_exec_team_escalated',
        ],
        RolesEnum::SUPER_ADMIN->value => [
            'recruiter_coverage_target',
            'recruiter_escalated',
            'tl_manager_escalated',
            'tl_manager_whole_home_escalated',
            'exec_team_escalated',
            'coverage_escalated',
            'coverage_tl_manager_escalated',
            'coverage_tl_manager_whole_home_escalated',
            'coverage_exec_team_escalated',
        ],
    ];

    /**
     * @param list<int> $roleIds
     * @return list<string>
     */
    public function visibleColumnKeys(array $roleIds): array
    {
        $visible = [];

        foreach ($roleIds as $roleId) {
            $columns = self::ROLE_COLUMNS[$roleId] ?? [];
            foreach ($columns as $column) {
                $visible[$column] = true;
            }
        }

        if (empty($visible)) {
            return [self::DEFAULT_COLUMN];
        }

        $ordered = [];
        foreach (array_keys(self::COLUMN_LABELS) as $columnKey) {
            if (isset($visible[$columnKey])) {
                $ordered[] = $columnKey;
            }
        }

        return $ordered;
    }

    /**
     * @param list<string> $visibleColumnKeys
     * @return list<array{key:string,label:string}>
     */
    public function visibleColumns(array $visibleColumnKeys): array
    {
        $columns = [];

        foreach ($visibleColumnKeys as $key) {
            $columns[] = [
                'key' => $key,
                'label' => self::COLUMN_LABELS[$key] ?? $key,
            ];
        }

        return $columns;
    }
}
