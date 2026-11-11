<?php

namespace App\Console\Commands;

use App\CustomFields;
use App\Helpers\H;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-off repair: Kantime sync used to overwrite mssql_patients.PreferenceList, so it drifted
 * from the aoc_cf_* Preferences custom fields shown on the client profile.
 *
 * Step A rebuilds PreferenceList from aoc_cf_* with the same rule as EditableController
 * (Preferences custom field edit). Patients with no aoc_cf_* preference value at all are skipped.
 *
 * Step B recalculates only the maps_nth columns derived from PreferenceList, with the same rules
 * as NTHChecklist::AddEditNTHMapRecord. Nothing else on the map row is touched (no needs_geocoding).
 */
class RebuildClientPreferenceList extends Command
{
    protected $signature = 'clients:rebuild-preference-list
        {--dry-run : Only report what would change, write nothing.}';

    protected $description = 'Rebuild mssql_patients.PreferenceList from aoc_cf_* Preferences fields and refresh the NTH map columns derived from it.';

    private const LOG_FILE = 'rebuildPreferenceList.log';
    private const CHUNK_SIZE = 500;
    private const FEMALE_NURSES_FIELD = 'aoc_cf_female_nurses';

    /**
     * maps_nth column => PreferenceList phrase, as in NTHChecklist::AddEditNTHMapRecord.
     */
    private const MAP_PHRASE_COLUMNS = [
        'Attending_School' => 'nurse attending child',
        'Smokers_Live_in_Home' => 'smokers live in home',
        'NonSmoking_Nurses' => 'client prefers non-smoking',
        'MediaConsent' => 'media consent form on file',
        'Covid_Vaccine_Required' => 'nurses must be covid-19 vaccin',
    ];

    private bool $dryRun = false;

    /**
     * Patient id => rebuilt PreferenceList, so step B sees step A's result in dry-run too.
     */
    private array $rebuiltLists = [];

    public function handle()
    {
        $this->dryRun = (bool) $this->option('dry-run');

        if ($this->dryRun) {
            $this->warn('Dry run: nothing will be written.');
        }
        H::ExcelLog('Start ' . ($this->dryRun ? '(dry run)' : ''), self::LOG_FILE);

        $this->rebuildPreferenceLists();
        $this->refreshNthMapColumns();

        H::ExcelLog('Finished', self::LOG_FILE);

        return 0;
    }

    private function rebuildPreferenceLists(): void
    {
        $tagsByField = $this->getPreferenceTagsByField();

        if (count($tagsByField) === 0) {
            $this->error('No client Preferences custom fields found.');

            return;
        }

        $fields = array_keys($tagsByField);
        $stats = ['scanned' => 0, 'skipped' => 0, 'changed' => 0, 'unchanged' => 0];

        DB::table('mssql_patients')
            ->select(array_merge(['id', 'PreferenceList'], $fields))
            ->chunkById(self::CHUNK_SIZE, function ($patients) use ($tagsByField, $fields, &$stats) {
                foreach ($patients as $patient) {
                    $stats['scanned']++;

                    $hasValue = false;
                    foreach ($fields as $field) {
                        if (!is_null($patient->{$field})) {
                            $hasValue = true;
                            break;
                        }
                    }

                    if (!$hasValue) {
                        $stats['skipped']++;
                        continue;
                    }

                    $newList = $this->buildPreferenceList($tagsByField, $patient);

                    if ($newList === (string) $patient->PreferenceList) {
                        $stats['unchanged']++;
                        continue;
                    }

                    $stats['changed']++;
                    $this->rebuiltLists[$patient->id] = $newList;
                    H::ExcelLog('Patient ' . $patient->id . ' PreferenceList: "' . $patient->PreferenceList . '" => "' . $newList . '"', self::LOG_FILE);

                    if (!$this->dryRun) {
                        DB::table('mssql_patients')->where('id', $patient->id)->limit(1)->update(['PreferenceList' => $newList]);
                    }
                }
            });

        $this->info('Step A - mssql_patients.PreferenceList');
        $this->table(array_keys($stats), [array_values($stats)]);
        H::ExcelLog('Step A: ' . json_encode($stats), self::LOG_FILE);
    }

    /**
     * Same field set as EditableController, minus fields without a tag (they would add empty entries).
     */
    private function getPreferenceTagsByField(): array
    {
        $tagsByField = [];
        $preferenceFields = CustomFields::select('db_field_name', 'preference_tag')
            ->where('is_deleted', 0)
            ->where('category', 'Preferences')
            ->where('entity', 'client')
            ->get();
        foreach ($preferenceFields as $field) {
            if (empty($field->preference_tag) || !Schema::hasColumn('mssql_patients', $field->db_field_name)) {
                continue;
            }
            $tagsByField[$field->db_field_name] = $field->preference_tag;
        }

        return $tagsByField;
    }

    /**
     * Same rule as EditableController: tag is added for 'Yes', except Female Nurses which is added for 'No'.
     */
    private function buildPreferenceList(array $tagsByField, object $patient): string
    {
        $newPreferenceList = [];
        foreach ($tagsByField as $field => $tag) {
            $val = $patient->{$field};

            if ($val === 'Yes' && $field !== self::FEMALE_NURSES_FIELD) {
                $newPreferenceList[] = $tag;
            }

            if ($val === 'No' && $field === self::FEMALE_NURSES_FIELD) {
                $newPreferenceList[] = $tag;
            }
        }

        return implode(',', $newPreferenceList);
    }

    private function refreshNthMapColumns(): void
    {
        $mapColumns = array_merge(array_keys(self::MAP_PHRASE_COLUMNS), ['Clinician_Gender']);
        $stats = ['scanned' => 0, 'changed' => 0];
        $columnStats = array_fill_keys($mapColumns, 0);

        $select = ['maps_nth.id as map_id', 'p.id as patient_id', 'p.PreferenceList', 'p.aoc_cf_male_nurses', 'p.aoc_cf_female_nurses'];
        foreach ($mapColumns as $column) {
            $select[] = 'maps_nth.' . $column;
        }

        DB::table('maps_nth')
            ->join('NeedToHire_Accounts as a', 'a.id', '=', 'maps_nth.aoc_nthaccounts_id')
            ->join('mssql_patients as p', 'p.id', '=', 'a.aoc_patient_id')
            ->select($select)
            ->chunkById(self::CHUNK_SIZE, function ($rows) use (&$stats, &$columnStats) {
                foreach ($rows as $row) {
                    $stats['scanned']++;
                    $preferenceList = $this->rebuiltLists[$row->patient_id] ?? (string) $row->PreferenceList;

                    $newValues = [];
                    foreach (self::MAP_PHRASE_COLUMNS as $column => $phrase) {
                        $newValues[$column] = strpos(strtolower($preferenceList), $phrase) !== false ? 'Yes' : 'No';
                    }
                    $newValues['Clinician_Gender'] = $this->getClinicianGender($row, $preferenceList);

                    $changes = [];
                    foreach ($newValues as $column => $value) {
                        if ((string) $row->{$column} !== $value) {
                            $changes[$column] = $value;
                            $columnStats[$column]++;
                        }
                    }

                    if (count($changes) === 0) {
                        continue;
                    }

                    $stats['changed']++;
                    $old = [];
                    foreach ($changes as $column => $value) {
                        $old[$column] = $row->{$column};
                    }
                    H::ExcelLog('maps_nth ' . $row->map_id . ': ' . json_encode($old) . ' => ' . json_encode($changes), self::LOG_FILE);

                    if (!$this->dryRun) {
                        DB::table('maps_nth')->where('id', $row->map_id)->limit(1)->update($changes);
                    }
                }
            }, 'maps_nth.id', 'map_id');

        $this->info('Step B - maps_nth preference columns');
        $this->table(array_keys($stats), [array_values($stats)]);
        $this->table(['column', 'changed'], collect($columnStats)->map(fn ($count, $column) => [$column, $count])->values()->all());
        H::ExcelLog('Step B: ' . json_encode($stats) . ' ' . json_encode($columnStats), self::LOG_FILE);
    }

    /**
     * Same rule as NTHChecklist::AddEditNTHMapRecord.
     */
    private function getClinicianGender(object $row, string $preferenceList): string
    {
        $genderPref = 'Either Gender';

        if (isset($row->aoc_cf_male_nurses) && isset($row->aoc_cf_female_nurses)) {
            if ($row->aoc_cf_female_nurses === 'No') {
                $genderPref = 'Male Only';
            } elseif ($row->aoc_cf_male_nurses === 'No') {
                $genderPref = 'Female Only';
            }
        } else {
            $preferenceList = strtolower($preferenceList);

            if (str_contains($preferenceList, 'does not accept female nurses')) {
                $genderPref = 'Male Only';
            } elseif (!str_contains($preferenceList, 'male nurse accepted')) {
                $genderPref = 'Female Only';
            }
        }

        return $genderPref;
    }
}
