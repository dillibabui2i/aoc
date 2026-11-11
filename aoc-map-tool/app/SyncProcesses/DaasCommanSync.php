<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Carbon\Carbon;

class DaasCommanSync
{

    public function syncComman($options)
    {
        $syncType = $options['syncType'];
        $tableName = $options['tableName'];
        $fieldName = $options['fieldName'];
		$callbackFunction = $options['callback']??'';

		$initSync = isset($options['init']) ? $options['init'] : 0;

        //start batch
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo 'stop process';
            exit;
        }
        $batch_info = H::getHelper('Sync')->get_batch_information($syncType);
        var_dump($batch_info);
        echo "\n";

        $last_updated_date = $batch_info['last_updated_date'];
        $last_updated_id = $batch_info['last_updated_id'];
		$sfQuery = $batch_info['comment'];
    	echo 'last_updated_id: ' . $last_updated_id . "\n";
        $limit = $batch_info['batch_size'];
		$combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
		if (empty($sfQuery)) {
			$query = $this->createTableAndQuery($syncType, $tableName,
				$options, $fieldName, $limit);
		} else {
			$query = $sfQuery;
		}
		$newQuery = str_replace("#combinedString", $combinedString, $query);
		echo "\n" . $newQuery . "\n";
        $rawData = DB::connection('daas')->select(DB::raw(str_replace("#combinedString", $combinedString, $query)));
        $data = json_decode(json_encode($rawData), true);

        $recordCount = 0;
		$insertCount = 0;
		$updateCount = 0;
		$deleteCount = 0;
        foreach($data as $row) {
            $recordCount++;
            var_dump($row);
            echo "\n";

            $last_updated_date = $row['LASTMODIFIED'];
            $last_updated_id = $row[$fieldName];
			echo 'last updated: ' . $last_updated_date . ' - ' . $last_updated_id . "\n";
			$editType = $this->processRow($tableName, $fieldName, $row, $initSync);
			switch ($editType) {
				case 'D':
					$deleteCount++;
					!empty($callbackFunction) ? call_user_func($callbackFunction, $row, 'Delete') : '';
					break;
				case 'E':
					$updateCount++;
					!empty($callbackFunction) ? call_user_func($callbackFunction, $row, 'Update') : '';
					break;
				case 'I':
					$insertCount++;
					!empty($callbackFunction) ? call_user_func($callbackFunction, $row, 'Insert') : '';
					break;
				default:
					// there are only Delete, Edit, and Inserts
					break;
			}
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        echo "sync finished \n";
		echo "Records Processed: " . $recordCount . "\n";
		echo "Records Inserted: " . $insertCount . "\n";
		echo "Records Updated: " . $updateCount . "\n";
		echo "Records Deleted: " . $deleteCount . "\n";
        H::getHelper('Sync')->stopBatch($syncType);
    }

	private function processRow($tableName, $fieldName, $row, $initSync)
	{
		$newRow = [];
		$editType = '';
		$newRow = $this->setNewRow($tableName, $row, $newRow);
		$newRow['created_by'] = 1;
		$newRow['created_at'] = date_format(date_create(),
			config('constant')['longdatetimeformat']);

		$uniqueID = $row[$fieldName];
		if($initSync == 0){
			$dbRecord = DB::select("select * from $tableName where `$fieldName` = '" . $uniqueID . "'");
			if (isset($row['ISVALIDRECORD']) && $row['ISVALIDRECORD'] == 0 && $initSync == 0) {
				echo 'Deleted Record ' . $uniqueID . "\n";
				$editType = 'D';
				DB::table($tableName)->where($fieldName, $uniqueID)->delete();
			} elseif (!empty($dbRecord)) {
				echo 'Update Record ' . $uniqueID . "\n";
				$editType = 'E';
				$newRow['updated_by'] = 1;
				$newRow['updated_at'] = date_format(date_create(),
					config('constant')['longdatetimeformat']);

				DB::table($tableName)->whereRaw("`$fieldName`= ?", [$uniqueID])->update($newRow);
			} else {
				echo 'Insert Record ' . $uniqueID . "\n";
				$editType = 'I';
				DB::table($tableName)->insert($newRow);
			}
		}else{
			echo 'Insert Record ' . $uniqueID . "\n";
			$editType = 'I';
			DB::table($tableName)->insert($newRow);
		}
		return $editType;
	}

	private function setNewRow($tableName, $row, $newRow){
		foreach ($row as $key => &$value) {
			if (is_null($value)) {
				$value = '';
			}
			$newKey = str_replace(' ', '_', $key);

			if (Schema::hasColumn($tableName, $newKey)) {
				if ($tableName === 'journal_notes' && in_array($newKey, ['note_cleared_date', 'note_cleared_by'])) {
					continue;
				}
				$newRow[$newKey] = (!is_numeric($value) && empty($value)) ? null : $value;
			}
			$newRow['created_by'] = 1;
			$newRow['created_at'] = date_format(date_create(),
				config('constant')['longdatetimeformat']);
		}
		return $newRow;
	}

	private function createTableAndQuery($syncType, $tableName, $options, $fieldName, $limit){
		$dbquery = "SELECT cast(COLUMN_NAME as varchar(100)) as Columnname,
			cast(DATA_TYPE  as varchar(100)) as datatype
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_NAME = '".$options['sfTableName'].
				"' AND TABLE_SCHEMA = 'HH_REPORT_DW'
			ORDER BY ORDINAL_POSITION ASC";
		$dbRawData = DB::connection('daas')->select(DB::raw($dbquery));
		$dbData = json_decode(json_encode($dbRawData), true);
		$selectQuery = $this->buildSelectQuery($dbData,$options);

		$customFieldsArray = [];
		$customFields = '';
		if (isset($options['customFields'])) {
			foreach ($options['customFields'] as $customFieldAlias => $customField) {
				$customFieldsArray[] = $customField . ' as ' . $customFieldAlias;
				array_push($dbData, ['COLUMNNAME' => $customFieldAlias, 'DATATYPE' => 'VARCHAR']);
			}
		}

		if (count($customFieldsArray) > 0) {
			$customFields = implode(", ", $customFieldsArray);
		}

		$query = "select top " . $limit .
			" cast(ETL_UPDATED_TIMESTAMP as varchar(50)) as LastModified
			" . (!empty($customFields) ? ',' . $customFields : '') . "
			,concat(cast(ETL_UPDATED_TIMESTAMP as varchar(50)),
			cast(".(isset($options['suffix_ignore'])
			&& in_array($fieldName, $options['suffix_ignore']) ? $fieldName :
			$options['suffix'].$fieldName)." as varchar(50))) as CombinedString,
			".implode(" , ", $selectQuery)."
			from " . $options['sfTableName'] . "
			WHERE CombinedString > '#combinedString' order by CombinedString ASC";
		DB::table('process_settings')
			->where('process', $syncType)
			->update(['comment' => $query]);

		if (!Schema::hasTable($tableName)) {
			$this->createTable($tableName, $dbData,$options);
		}
		return $query;
	}

	private function createTable($tableName, $dbData,$options){
		Schema::create($tableName, function (Blueprint $table) use ($dbData, $options) {
			$table->increments('id');
			foreach ($dbData as $dbRow) {
				if (isset($options['ignore']) && in_array($dbRow['COLUMNNAME'], $options['ignore']) !== false) {
					continue;
				}
				$columnName = strtolower(((isset($options['suffix_ignore'])
					&& in_array($dbRow['COLUMNNAME'],
					$options['suffix_ignore'])) ? $dbRow['COLUMNNAME'] :
					str_replace($options['suffix'],"",$dbRow['COLUMNNAME'])));
				$columnName = (
					isset($options['replaceColumnName']) &&
					array_key_exists($dbRow['COLUMNNAME'], $options['replaceColumnName'])
				) ? $options['replaceColumnName'][$dbRow['COLUMNNAME']] : $columnName;
				if ($dbRow['DATATYPE'] == 'VARCHAR') {
					$table->string($columnName, 1000)
					->nullable();
				} elseif ($dbRow['DATATYPE'] == 'TEXT') {
					$table->text($columnName)
					->nullable();
				} elseif($dbRow['DATATYPE'] == 'NUMBER') {
					$table->integer($columnName)
					->nullable();
				} elseif($dbRow['DATATYPE'] == 'BOOLEAN') {
					$table->boolean($columnName)
					->nullable();
				} elseif($dbRow['DATATYPE'] == 'TIMESTAMP_NTZ') {
					$table->timestamp($columnName)
					->nullable();
				} elseif($dbRow['DATATYPE'] == 'FLOAT') {
					$table->decimal($columnName, 14, 4)
					->nullable();
				} elseif($dbRow['DATATYPE'] == 'DATE') {
					$table->date($columnName)
					->nullable();
				}
			}
			$table->integer('created_by')->nullable();
			$table->timestamp('created_at')->useCurrent();
			$table->integer('updated_by')->nullable();
			$table->timestamp('updated_at')
				->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
			$table->boolean('is_deleted')->nullable();
		});
	}

	private function buildSelectQuery($dbData,$options){
		$selectQuery = [];
		foreach($dbData as $dbRow) {
			if (isset($options['ignore']) && in_array($dbRow['COLUMNNAME'], $options['ignore']) !== false) {
				continue;
			}
			$columnName = ((isset($options['suffix_ignore'])
				&& in_array($dbRow['COLUMNNAME'],
				$options['suffix_ignore'])) ? $dbRow['COLUMNNAME'] :
				str_replace($options['suffix'],"",$dbRow['COLUMNNAME']));
			$columnName = (
					isset($options['replaceColumnName']) &&
					array_key_exists($dbRow['COLUMNNAME'], $options['replaceColumnName'])
				) ? $options['replaceColumnName'][$dbRow['COLUMNNAME']] : $columnName;
			if ($dbRow['DATATYPE'] == 'VARCHAR') {
				$selectQuery[] = 'cast(' . $dbRow['COLUMNNAME'] .
					' as varchar(1000)) as ' . $columnName;
			} elseif ($dbRow['DATATYPE'] == 'TEXT') {
				$selectQuery[] = 'cast(' . $dbRow['COLUMNNAME'] .
					' as text) as ' . $columnName;
			} elseif ($dbRow['DATATYPE'] == 'BOOLEAN') {
				$selectQuery[] = 'cast(' . $dbRow['COLUMNNAME'] .
					' as integer) as ' . $columnName;
			} else {
				$selectQuery[] = $dbRow['COLUMNNAME'] . ' as ' .
					$columnName;
			}
		}
		return $selectQuery;
	}
}
