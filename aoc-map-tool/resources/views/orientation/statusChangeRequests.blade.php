<?php
use App\Helpers\H;
use App\Helpers\LD;

$ocTaskStatusList  = config('constant')['statusChangeRequestTaskStatusDashboard'];
$ocTaskStatus  = [];
foreach($ocTaskStatusList as $br =>$vl) {
    $ocTaskStatus[] = ["value"=>$vl,'text'=>$vl];
}
$ocNewStatus  = H::getHelper('StatusChangeRequests')->getNewClinicianStatus();
$ocStatusChangeRequestFilter = [
        [
            'label'=>'New Status',
            'name'=>'ocNewStatus',
            'type'=>'multiselect',
            'source'=>$ocNewStatus
        ],
        [
            'label'=>'Task Status',
            'name'=>'ocTaskStatus',
            'type'=>'multiselect',
            'source'=>$ocTaskStatus
        ]
    ];
?>


<div class="card-body">
    <div class="card">
        <div class="card-header" id="dailyactivityone">
            <h4 class="mb-0">
                <button class="btn btn-link btn-block text-left collapsed col-md-9 pull-left" type="button" data-toggle="collapse"
                    data-target="#oc_status_change_requests" aria-expanded="false" aria-controls="collapseOne">
                    Status Change Requests <span id="oc_status_change_requests-counter"></span><i class="fa fa-chevron-right"></i>
                </button>
                <div class="col-md-3 pr-2 text-right pull-left"><a href="{{route('statusChangeRequest.showAll')}}" class="dashboard-showall" title="Show all Status Change Requests">Show all Status Change Requests</a></div>

            </h4>
        </div>

        <div id="oc_status_change_requests" class="collapse" aria-labelledby="dailyactivityone" data-parent="#orientation">
            <p style="text-align: left;"><u><b>Responsible Party:</b> Orientation Coordinator</u><br>
                <b>Description:</b> Clinicians who have been requested to have the employment status changed. Please ensure that all necessary steps are taken before changing statuses in KanTime. Prompt action is essential in maintaining compliance and ensuring the continuity of client services.<br>
                <b>Timeline:</b> Actions are to be taken within 1 hour of request.</p>
            <div class="filters-common">
                @component('components.datatable-filters', ['filters' => $ocStatusChangeRequestFilter, 'formname' => 'oc_status_change_requests_form'])
                @endcomponent
            </div>
            <div class="card-body">
                <?php  echo H::getHelper("StatusChangeRequests")->getDataTableHtml("oc_status_change_requests")?>
            </div>
        </div>
    </div>
</div>


<script>
        const oc_status_change_requests_bucket = ["oc_status_change_requests"];

        $(document).on('submit', '#orientationForm,#oc_status_change_requests_form', function(e) {
            e.preventDefault();
            var sData = $("#orientationForm,#oc_status_change_requests_form").serializeArray();
            for(var i=0; i < oc_status_change_requests_bucket.length;i++) {
                var tempObject = oc_status_change_requests_bucket[i].replace(/ /g,"_");
                if($("#reload-"+tempObject).val()==1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');

                    $.ajax({
                        url: "{{route('statuschangerequests.reloadCountForSupplyRequests')}}?type="+oc_status_change_requests_bucket[i],
                        type: "POST",
                        data: {
                            filters: sData,
                            _token: _token
                        },
                        success: function(response) {
                            var jsonObject = JSON.parse(response);
                            var type = jsonObject.type;
                            var idObject = type.replace(/ /g,"_");
                            $("#"+idObject+'-counter').html(jsonObject.counter);
                        },
                    });
                } else {
                   $("#dataTable-changeRequest-"+tempObject).DataTable().ajax.reload();
                }
            }

        });

</script>
