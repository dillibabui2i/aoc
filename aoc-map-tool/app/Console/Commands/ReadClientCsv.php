<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use DB;
use App\Helpers\H;

class ReadClientCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:read';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads a CSV file from the storage folder';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $file = storage_path('app/Nik-customfields.csv');

        if (file_exists($file) && ($handle = fopen($file, 'r')) !== false) {
            $this->info('Reading CSV file...');

            $headers = fgetcsv($handle);
            $clientValue = [];
            $keyMapping = [
                'GoalSOCDate' => 'CF_GoalSOCDate'
            ];
            while (($row = fgetcsv($handle)) !== false) {
                $jsonString = $row[1];
                $patient_id = $row[0];
				//echo $patient_id."\n";
				//echo $jsonString;exit;
                H::ExcelLog('Update this PatientAccount value: ' .$patient_id, 'clientCsv.log');
				echo 'Update this PatientAccount value: ' .$patient_id;
                $jsonArray = json_decode($jsonString, true);
                //print"<pre>";print_r($jsonArray);exit;
                if ($jsonArray !== null) {
                    $clientValue = [];
                    foreach ($keyMapping as $jsonKey => $dbField) {
                        if (array_key_exists($jsonKey, $jsonArray)) {
                            $value = self::getJsonValue($jsonArray, $jsonKey);
                            if (!empty($value) && $value != 0) {
                                $clientValue[$dbField] = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
                            }
                        }
                    }
                } else {
                    $this->info("Invalid JSON format in row");
                }
				/*if (!empty($clientValue)) {
					echo "\n";
					echo $patient_id;
                print"<pre>";print_r($clientValue);exit;
				}*/
                if (!empty($clientValue)) {
                    $currentValues = DB::table('mssql_patients')
                    ->select(array_values($keyMapping))
                    ->where('IntakeID', $patient_id)
                    ->first();

                    if ($currentValues) {
                        $updateData = [];
                        foreach ($clientValue as $dbField => $newValue) {
                            if (is_null($currentValues->$dbField) || $currentValues->$dbField == ''  || $currentValues->$dbField == '0000-00-00 00:00:00') {
                                $updateData[$dbField] = $newValue;
                                H::ExcelLog("Update this $dbField value: " . $newValue, 'clientCsv.log');
                            }
                        }
                        if (!empty($updateData)) {
                            DB::table('mssql_patients')->where('IntakeID', $patient_id)->update($updateData);
                            H::ExcelLog('Update Done', 'clientCsv.log');
                        } else {
                            H::ExcelLog('No fields to update for IntakeID: ' . $patient_id, 'clientCsv.log');
                        }
                    } else {
                        H::ExcelLog('Patient not found: ' . $patient_id, 'clientCsv.log');
                    }
                }
            }
			echo "Process finished";
            fclose($handle);
        } else {
            $this->error('File not found or cannot be opened.');
        }
    }

    public function getJsonValue($jsonArray, $key) {
        if (array_key_exists($key, $jsonArray)) {
            return $jsonArray[$key];
        } else {
            return "Key not found";
        }
    }
}
