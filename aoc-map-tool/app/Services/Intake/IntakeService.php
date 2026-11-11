<?php

namespace App\Services\Intake;

use App\Helpers\LD;

class IntakeService
{
    private const MAP = [
        'AuthorizedWeeklyHours' => [1002, 1028],
        'HHAAuthorizedWeeklyHours' => [1042, 1043, 1007],
        'CNAAuthorizedWeeklyHours' => [1030],
        'CF_RespWeeklyHrs' => [1111, 2222],
    ];

    private const DEFAULT = 'AuthorizedWeeklyVisits';

    public function resolvePatientHoursField(int $lobId): string
    {
        foreach (self::MAP as $field => $lobIds) {
            if (in_array($lobId, $lobIds, true)) {
                return $field;
            }
        }

        return self::DEFAULT;
    }

    public function getPatientHoursFieldCaseSql(string $lobColumn = 'lob.LOBID', string $alias = 'mssql_patients'): string
    {
        $cases = [];

        foreach (self::MAP as $field => $lobIds) {
            $ids = implode(',', $lobIds);
            $cases[] = "WHEN {$lobColumn} IN ({$ids}) THEN {$alias}.{$field}";
        }

        return "
            CASE
                " . implode("\n", $cases) . "
                ELSE {$alias}." . self::DEFAULT . "
            END
        ";
    }

    public function buildNonAdmitActivityNote(string $userName, array $requestData): string
    {
        $baseNote = $userName . ' requested client be non admitted.' . '<br>';

        $noteParts = [$baseNote];

        $newDetailsFlag = LD::checkFeatureFlag('add-non-admit-detail-to-client-activity-log');
        $oldCommentsFlag = LD::checkFeatureFlag('intake-dashboard-add-comments-to-non-admit-note');

        if ($newDetailsFlag) {
            $noteParts[] = 'Non Admit Reason: ' . ($requestData['intakeStaffForm_nonadmitreasons'] ?? '') . '<br>';
            $noteParts[] = 'Controllable vs Uncontrollable: ' . ($requestData['intakeStaffForm_contrbluncontrbl'] ?? '')
                . '<br>';

            $excludeInfo = ((isset($requestData['intakeStaffForm_excludefromreporting'])
                && $requestData['intakeStaffForm_excludefromreporting']) ? 'Yes' : 'No')  . '<br>';

            $noteParts[] = 'Lead: Exclude from Reporting: ' . $excludeInfo;

            $noteParts[] = 'Comments: ' . ($requestData['intakeStaffForm_comments'] ?? '');
        } elseif ($oldCommentsFlag) {
            $noteParts[] = 'Comments: ' . ($requestData['intakeStaffForm_comments'] ?? '');
        }

        return implode(' ', $noteParts);
    }


    /**
     * The SQL query's MIN(BD_Rep) is computed per (BDRepKey, facility, period) group, so different
     * facilities/weeks for the same rep can end up with different raw name variants selected. Pick
     * a single display name per BDRepKey across the whole result set so rows for the same rep stay
     * grouped together in the view (which groups visually by the BD_Rep display text).
     */
    public function resolveBdRepDisplayNames(array $tmp): array
    {
        $displayNames = [];
        foreach ($tmp as $record) {
            $key = $record['BDRepKey'];
            if (!isset($displayNames[$key]) || $record['BD_Rep'] < $displayNames[$key]) {
                $displayNames[$key] = $record['BD_Rep'];
            }
        }
        return $displayNames;
    }
}
