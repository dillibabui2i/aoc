<?php

namespace App\Repositories\GpCalculator;

use App\DTO\GpCalculator\ListGpDTO;
use Illuminate\Support\Facades\DB;

class GpCalculatorRepository
{
    /**
     * @param string $originalJson
     * @param int $userId
     * @param int $patientId
     * @param int|null $id
     * @return int saved calculator id
     */
    public function save(string $originalJson, int $userId, int $patientId, int $id = null) : int
    {
        if (is_numeric($id)) {
            DB::table('gp_calculator')->where('id', $id)->update([
                'nurse_data' => $originalJson,
                'aoc_patient_id' => $patientId,
                'user_id' => $userId,
                'updated_at' => new \DateTime(),
                'updated_by' => $userId,
            ]);

            return $id;
        }

        return DB::table('gp_calculator')->insertGetId([
                'nurse_data' => $originalJson,
                'aoc_patient_id' => $patientId,
                'user_id' => $userId,
                'updated_at' => new \DateTime(),
                'created_at' => new \DateTime(),
                'updated_by' => $userId,
                'created_by' => $userId,
        ]);
    }

    public function getList(ListGpDTO $cmd) : array
    {
        $q = DB::table('gp_calculator')
            ->leftJoin('mssql_patients', 'gp_calculator.aoc_patient_id', '=', 'mssql_patients.id')
            ->leftJoin('users', 'users.id', '=', 'gp_calculator.user_id')
            ->select(
                'gp_calculator.*',
                'users.name as userName',
                DB::raw("COALESCE(NULLIF(TRIM(CONCAT(IFNULL(mssql_patients.Patient_FirstName,''), ' ', IFNULL(mssql_patients.Patient_LastName,''))), ''), CONCAT('Custom Client #', gp_calculator.id)) AS client"),
                DB::raw("DATE_FORMAT(gp_calculator.created_at, '" . config('constant')['mysqlsearchdatetimeformat'] . "') as created_at"),
                DB::raw("DATE_FORMAT(gp_calculator.updated_at, '" . config('constant')['mysqlsearchdatetimeformat'] . "') as updated_at")
            );

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if (!empty($search)) {
            $q->where(function ($query) use ($search) {
                $query->where('mssql_patients.Search_Name', 'LIKE', "%{$search}%");
            });
            $totalFiltered = $q->count();
        }

        $data = $q->offset($cmd->start)->limit($cmd->limit)->orderBy($cmd->order, $cmd->dir)->get();

        return [
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $data,
        ];
    }

    public function get($id) : object
    {
        $res = DB::table('gp_calculator')->find($id);
        return $res;
    }
}
