<?php

namespace App\Helpers;

use App\Helpers\LD;
use Illuminate\Support\Facades\DB;

class Payrate
{
    public function getPayrateApprovals($request, $showCount)
    {
        $request = $request->all();

        $payrateDashboard = DB::table('mssql_recruitertracker')->select('mssql_recruitertracker.*','msqlfac.FacilityName',DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"));
        $payrateDashboard->leftJoin("mssql_patients", function ($query) {
            $query->on("mssql_recruitertracker.aoc_patient_id", '=', 'mssql_patients.id');
        });
        $payrateDashboard->leftJoin("mssql_facilities as msqlfac", function ($query) {
            $query->on("mssql_patients.aoc_facility_id", '=', 'msqlfac.id');
        });
        $payrateDashboard->where('payrate_approved', 0);

        $filters = [];
        if(isset($request['filters'])) {
            foreach($request['filters'] as $fl) {
                if(!empty($fl['value'])) {
                    $filters[$fl['name']][] = $fl['value'];
                }
            }
        }
        foreach($filters as $fkey => $fval) {
            if ($fkey == 'payrate-aoc_facility_id'){
                $payrateDashboard->where(function ($payrateDashboard) use ($fval) {
                    $payrateDashboard->whereIn("mssql_patients.aoc_facility_id", $fval);
                });
            } else if ($fkey == 'payrate-Recruiter'){
                $payrateDashboard->where(function ($payrateDashboard) use ($fval) {
                    $payrateDashboard->whereIn("mssql_recruitertracker.Recruiter", $fval);
                });
            } else if ($fkey == 'payrate-Manager'){
                $payrateDashboard->where(function ($payrateDashboard) use ($fval) {
                    $payrateDashboard->whereIn("mssql_recruitertracker.Approved_By", $fval);
                });
            }
        }
        
        if($showCount) {
            return '('.$payrateDashboard->count().')';
        }
        return $payrateDashboard;
    }

    public function getDataTableHtml($type)
    {
        $fieldArray = [
            'id' => 'ID',
            'FacilityName' => 'Branch',
            'Recruiter' => 'Recruiter',
            'Candidate' => 'Candidate',
            'patient_name' => 'Client',
            'Agreed_Pay_Rate' => 'Pay Rate',
            'GP' => 'GP',
            'Approved_By' => 'Approved By',
        ];
        $norwap = 1;

        $tableHeading = '';
        $keyArray = [];
        $jsonField = '';
        foreach ($fieldArray as $key => $value) {
            $tableHeading .= '<th>' . $value . '</th>';
            $visible=true;
            if($key=='id') {
                $visible = false;
            }
            $keyArray[] = ['data'=>$key,'name'=>$key,'visible'=>$visible,"searchable"=>true];
        }
        $keyArray[count($keyArray)] = ['data'=>'Action', "searchable"=>false, "orderable" => false];
        $tableHeading .= '<th>Action</th>';
        $jsonField = json_encode($keyArray);
        $defaultSsort = 3;
        $defaultOrder = "desc";

        $url = route('dashboard.getPayrateApprovalsDashboard');
        $html = '<div class="table-responsive">
                <input type="hidden" name="reload-'.str_replace(' ', '_', $type).'" id="reload-'.str_replace(' ', '_', $type).'" value="1" />
                        <table class="table table-bordered" id="dataTable-' . str_replace(' ', '_', $type) . '" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    ' . $tableHeading . '
                                </tr>
                            </thead>
                        </table>
                    </div>';

        $html .= '<script type="text/javascript">
                var dataTableDelay = 1000;

                function '.str_replace(' ', '_', $type).'() {
                    var ' . str_replace(' ', '_', $type) . ' = $("#dataTable-' . str_replace(' ', '_', $type) . '").DataTable({
                        "order": [
                            ["'.$defaultSsort.'", "'.$defaultOrder.'"]
                        ],
                        "columnDefs": [

                            { className: "dt-nowrap", "targets": [' . $norwap . '] },
                            { className: "dt-nowrap", "targets": [-1] },

                        ],
                        "ajax": {
                            "url": "' . $url . '?type=' . $type . '",
                            data: function(d) {
                                d.filters = $("#payrateapprovals").serializeArray();
                            },
                        },
                        "deferRender": true,
                        "processing": true,
                        "serverSide": true,
                        "columns": ' . $jsonField . ',
                        "drawCallback": function (oSettings, json) {
                        ';
                        $html .= '$("#' . str_replace(' ', '_', $type) . '-counter  ").html("("+' . str_replace(' ', '_', $type) . '.page.info().recordsTotal+")");';
                        $html .= '},
                        "initComplete": function(settings, json) {

                            jQuery("#dataTable-' . str_replace(' ', '_', $type) . '_filter input").off("keyup.DT input.DT");
                            var searchDelay = null;
                            jQuery("#dataTable-' . str_replace(' ', '_', $type) . '_filter input").on("keyup", function(e) {

                                var search = jQuery("#dataTable-' . str_replace(' ', '_', $type) . '_filter input").val();

                                clearTimeout(searchDelay);
                                searchDelay = setTimeout(function() {
                                    if (search != null && search.length >=3)
                                    ' . str_replace(' ', '_', $type) . '.search(search).draw();
                                    if (search.length==0 && e.keyCode==8)
                                    ' . str_replace(' ', '_', $type) . '.search(search).draw();
                                }, dataTableDelay);
                            });
                        },

                    });
                }
                </script>';
        return $html;
    }
}
