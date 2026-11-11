<?php
use App\Helpers\H;
use App\Helpers\LD;
$lobStatus = H::getHelper("ConstantHelper")->getLobStatus();
$lob  = [];
$excludeLOB = ['PT', 'OT', 'ST'];
foreach($lobStatus as $br) {
    if(!in_array($br->LOBName, $excludeLOB)){
        $lob[] = ["value"=>$br->LOBID,'text'=>$br->LOBName];
    }
}
$excludeDiscipline = ["PT", "PTA", "OT", "OTA", "ST", "STA"];
$discipline = H::filterArrayByValues(H::getHelper('ConstantHelper')->getDiscipline(), $excludeDiscipline);

$superVisoryfilter = [
    [
        'label'=>'Me Mode',
        'name'=>'svdefault',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'svstate',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Region',
        'name'=>'svregion',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label'=>'Branch',
        'name'=>'svbranch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],
    [
        'label'=>'Clinical Care Manager',
        'name'=>'svclinicalcaremanager',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getClinicalCareManager()
    ],
    [
        'label' => 'LOB',
        'name' => 'LOBID',
        'type' => 'multiselect',
        'source' => $lob
    ],
    [
        'label' => 'Discipline',
        'name' => 'discipline',
        'type' => 'multiselect',
        'source' => $discipline
    ],
    [
        'label'=>'Sup Visit Due Date (Next sup visit)',
        'name'=>'visit_date_due',
        'type'=>'dateRangePicker',
        'fromName'=>'fromvisit_date_due',
        'toName'=>'tovisit_date_due',
        'fromdate'=>'',
        'todate'=>'',
    ],
];
if(LD::checkFeatureFlag('client-clinician-status-columns-and-filters-supervisory-visit-tracking-bucket')) {
    $employmentStatusList = H::getHelper('ConstantHelper')->getEmploymentStatus();
    $employmentStatus = [];
    foreach($employmentStatusList as $key => $value) {
        $employmentStatus[] = ['value' => $value->EmploymentStatusID, 'text' => $value->EmploymentStatus];
    }
    $patientStatus = H::getHelper("ConstantHelper")->getMssqlPatientStatus();
    $status = [];
    foreach($patientStatus as $ps){
        $status[] = ["value"=>$ps->id,'text'=>$ps->PatientStatus];
    }
    $superVisoryfilter[] =[
            'label'=>'Clinician Status',
            'name'=>'svClinicianStatus',
            'type'=>'multiselect',
            'source'=> $employmentStatus
    ];
    $superVisoryfilter[] = [
            'label' => 'Client Status',
            'name' => 'svPatient_CurrentStatusID',
            'type' => 'multiselect',
            'source' => $status
        ];
}
    $superBucketKey = 'super_visory_visits_bucket';
?>
<div class="card-body">

    <div class="card" data-follows-global="true">
        <div class="card-header" id="dailyactivityone">
            <h4 class="mb-0">
                <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button"
                    data-toggle="collapse" data-target="#<?php echo $superBucketKey?>" aria-expanded="false"
                    aria-controls="collapseOne">
                    Supervisory Visit Tracking <span id="<?php echo $superBucketKey?>-counter"></span> <i
                        class="fa fa-chevron-right"></i>
                </button>
                <div class="col-md-2 text-right pull-left"><a href="{{route('supervisoryVisits.showAll')}}"
                class="dashboard-showall" title="Show All Sup Visits">Show All Sup Visits</a></div>
            </h4>
        </div>

        <div id="<?php echo $superBucketKey?>" class="collapse " aria-labelledby="dailyactivityone"
        data-parent="#client_care_childs">
            <p style="text-align: left;">
                <u>
                    <b>Responsible Party:</b> Clinical Care Manager
                </u><br>
                <b>Description:</b> Supervisory Visits that need to be completed for a clinician where the Next Sup Visit date is today, in the past, or in the next 60 days. Records are created based on state-specific logic and evaluate the clinician's schedule with the client to identify a required supervisory visit.<br>
                <b>Timeline:</b> Review daily. Any record remaining in the bucket where the Next Sup Visit date is today or in the past will be highlighted in red.
            </p>
            <div class="filters-common">
                @component('components.datatable-filters', ['filters' => $superVisoryfilter, 'formname' =>
                'super_visory'])
                @endcomponent
            </div>
            <?php
                echo H::getHelper("SupervisoryVisits")->getDataTableHtml($superBucketKey);
            ?>
        </div>
    </div>
</div>
@include('supervisoryvisits.deletepopup')
<script type="text/javascript">
    const superVisory = ["{{$superBucketKey}}"];

    $(document).on('submit', '#super_visory', function (e) {
        e.preventDefault();
        var sData = $("#super_visory").serializeArray();
        for (var i = 0; i < superVisory.length; i++) {
            if ($("#reload-" + superVisory[i]).val() == 1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{route('supervisoryVisit')}}?type=" + superVisory[i],
                    type: "POST",
                    data: {
                        filters: sData,
                        _token: _token
                    },
                    success: function (response) {
                        var jsonObject = JSON.parse(response);
                        var type = jsonObject.type;
                        var idObject = type.replace(/ /g, "_");

                        $("#" + idObject + '-counter').html(jsonObject.counter);
                    },
                });
            } else {
                $("#dataTable-" + superVisory[i]).DataTable().ajax.reload();
            }
        }

    });
    $("#super_visory").trigger("submit");

    $(document).on("change", '#svstate', function () {
        var state = $(this).val().join(",");
        if (state.indexOf('TX') !== -1) {
            $('select[name="svregion"]').multiselect('enable');
        } else {
            $('select[name="svregion"]').multiselect('disable');
        }
        let _token = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: "{{ route('getstatebranch') }}",
            type: "GET",
            data: {
                state: state,
                _token: _token
            },
            success: function (response) {
                $('#svbranch').empty();
                $.each(response, function (index, value) {
                    $("#svbranch").append('<option value="' + value.id + '">' + value.FacilityName + '</option>')
                });
                $("#svbranch").multiselect("rebuild");
            },
        });
    });
</script>
