<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\MssqlPatients;

class MergeDuplicateClients extends Command
{
    protected $signature = 'clients:merge-duplicates {file}';
    protected $description = 'Merge duplicate MSSQL patients based on a CSV file with good and bad client IDs';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("CSV file not found: $filePath");
            return 1;
        }

        try {
            $csv = fopen($filePath, 'r');
            $header = fgetcsv($csv);
            $header = array_map(fn($h) => strtolower(trim($h)), $header);

            while (($row = fgetcsv($csv)) !== false) {
                $row = array_map('trim', $row); // Trim values
                $record = array_combine($header, $row);
                $goodClientId = $record['good_client_id'] ?? null;
                $badClientId = $record['bad_client_id'] ?? null;
                if (empty($goodClientId) || empty($badClientId)) {
                    H::ExcelLog("Skipping row due to missing data: " . json_encode($record), 'ClientMerge.log');
                    continue;
                }

                $goodClientDetails = MssqlPatients::select("mssql_patients.*")->where("mssql_patients.PatientAccount",$goodClientId)->orderBy('mssql_patients.created_at')->get();
                $goodClientId = $goodClientDetails[0]->id;

                $badClientDetails = MssqlPatients::select("mssql_patients.*")->where("mssql_patients.PatientAccount",$badClientId)->orderBy('mssql_patients.created_at')->get();
                $badClientId = $badClientDetails[0]->id;

                // Move all linked records
                $this->transferTable('mssql_patients', 'id', $badClientId, $goodClientId, [], [
                    'CF_Weight',
                    'AuthorizedWeeklyHours',
                    'CF_Skills',
                    'HHAAuthorizedWeeklyHours',
                    'CF_RespWeeklyHrs',
                    'CF_ServicesNeeded',
                    'CF_Skills',
                    'aoc_cf_media_consent',
                    'CF_MeetingNurses',
                    'PetsinHome',
                    'Languages',
                    'PreferenceList',
                    'CF_ClinicianDiscipline',
                    'PetsNurseCannotOwn',
                    'CF_NurseLanguage',
                    'CF_LiftingRequired',
                    'NTHNotes',
                    'CF_TheSale',
                    'Biography'
                ], true);
                $affectedNthAccountIds = $this->transferTable('NeedToHire_Accounts', 'aoc_patient_id', $badClientId, $goodClientId, [], [], false, ['ClientID' => $goodClientDetails[0]->PatientAccount]);
                if (!empty($affectedNthAccountIds)) {
                    DB::table('maps_nth')
                        ->whereIn('aoc_nthaccounts_id', $affectedNthAccountIds)
                        ->update(['ClientID' => $goodClientDetails[0]->PatientAccount]);
                }
                $this->transferTable('NeedToHire_Accounts_Audit', 'aoc_patient_id', $badClientId, $goodClientId);
                $this->transferTable('notes', 'patient_id', $badClientId, $goodClientId);
                $this->transferTable('notes', 'entity_id', $badClientId, $goodClientId, ['entity' => 'client']);
                $this->transferTable('notifications', 'related_item', $badClientId, $goodClientId, ['type' => 'client']);
                $this->transferTable('mssql_recruitertracker', 'aoc_patient_id', $badClientId, $goodClientId);
                $this->transferTable('recruiting_schedule', 'aoc_patient_id', $badClientId, $goodClientId);

                // Add activity note
                H::getHelper("ActivityNotes")->setActivityNotesFromConsole([
                    'entity' => 'client',
                    'entity_id' => $goodClientId,
                    'notes' => "Activity from a duplicate account ($badClientId) was merged into this account."
                ]);

                // Delete bad client
                DB::table('mssql_patients')->where('id', $badClientId)->delete();

                H::ExcelLog("Deleted bad client ID $badClientId after merge.", 'ClientMerge.log');
            }

            $this->info('Merge process completed.');
            fclose($csv);
        } catch (\Exception $e) {
            H::ExcelLog('Exception: ' . $e->getMessage(), 'ClientMerge.log');
            $this->error("Exception: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /*protected function transferTable($table, $column, $fromId, $toId, $whereExtra = [])
    {
        $query = DB::table($table)->where($column, $fromId);
        foreach ($whereExtra as $key => $value) {
            $query->where($key, $value);
        }

        $ids = $query->pluck('id')->toArray();

        if (!empty($ids)) {
            H::ExcelLog("Records from $table moved from $fromId to $toId during Merge Client process", 'ClientMerge.log');

            $updateQuery = DB::table($table)->where($column, $fromId);
            foreach ($whereExtra as $key => $value) {
                $updateQuery->where($key, $value);
            }

            $updateQuery->update([$column => $toId]);
        } else {
            H::ExcelLog("No records found in $table to move from $fromId to $toId during Merge Client process", 'ClientMerge.log');
        }
    }*/

    protected function transferTable($table, $column, $fromId, $toId, $whereExtra = [], $copyFields = [], $onlyCopyFields = false, $extraUpdateFields = [])
    {
        $query = DB::table($table)->where($column, $fromId);
        foreach ($whereExtra as $key => $value) {
            $query->where($key, $value);
        }

        $badRecords = $query->get();

        if ($badRecords->isEmpty()) {
            H::ExcelLog("No records found in $table to move from $fromId to $toId during Merge Client process", 'ClientMerge.log');
            return;
        }

        H::ExcelLog("Records from $table moved from $fromId to $toId during Merge Client process", 'ClientMerge.log');

        foreach ($badRecords as $badRecord) {
            if (!$onlyCopyFields) {
                $updateData = [$column => $toId];
            }

            foreach ($copyFields as $field) {
                if (isset($badRecord->$field)) {
                    $updateData[$field] = $badRecord->$field;
                }
            }

            $updateData = array_merge($updateData, $extraUpdateFields);

            $updateQuery = DB::table($table)
                ->where('id', (!$onlyCopyFields) ? $badRecord->id : $toId);

            foreach ($whereExtra as $key => $value) {
                $updateQuery->where($key, $value);
            }

            $updateQuery->update($updateData);
        }

        return $badRecords->pluck('id')->toArray();
    }
}
