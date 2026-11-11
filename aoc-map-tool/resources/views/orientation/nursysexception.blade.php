<?php
use App\Helpers\H;
use App\Helpers\LD;

$nursysExceptionFilter = [

        [
            'label'=>'Clinician status',
            'name'=>'nursys_clinicianstatus',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getRenewableClinicianStatus()
        ]


    ];
?>


<div class="card-body">
    <div class="card">
        <div class="card-header" id="dailyactivityone">
            <h4 class="mb-0">
                <button class="btn btn-link btn-block text-left collapsed col-md-10 pull-left" type="button" data-toggle="collapse"
                    data-target="#nursyslicensemismatch" aria-expanded="false" aria-controls="collapseOne">
                    Nursys License Mismatches <span id="nursyslicensemismatch-counter"></span><i class="fa fa-chevron-right"></i>
                </button>
                <?php if(LD::checkFeatureFlag('nursys-mismatch-show-all')){ ?>
                <div class="col-md-2 text-right pull-left"><a href="{{route('nursys.showAll')}}" class="dashboard-showall" title="Show All Nursys License Mismatches">Show All Nursys License Mismatches</a></div>
                <?php } ?>
            </h4>
        </div>

        <div id="nursyslicensemismatch" class="collapse" aria-labelledby="dailyactivityone"
            data-parent="#orientation">
            <p style="text-align: left;"><u><b>Responsible Party:</b> Orientation Coordinator</u><br>
            <b>Description:</b> Clinicians in a status of Active, Active Coverage, and Standby whose
            Professional License information entered into KanTime does not match the Nursys license
            information from the Board of Nursing.
            <a target="_blank"
               href="https://angelsofcare.sharepoint.com/teams/OrientationCoordinators/SitePages/Renewables---Draft.aspx">
            Visit the Detailed Training Page</a><br>
            <b>Timeline:</b> Review daily &mdash; all records should be cleared by the end of each day, with any
            issues escalated to leadership immediately each day. Any record still remaining in the bucket
            1 business day from the created date will be highlighted in red.</p>
            <div class="filters-common">
                @component('components.datatable-filters', ['filters' => $nursysExceptionFilter, 'formname' => 'nursyslicensemismatchform'])
                @endcomponent
            </div>
            <div class="card-body">
                <?php  echo H::getHelper("NursysMismatchException")->getDataTableHtml("nursyslicensemismatch")?>
            </div>
        </div>
    </div>
</div>


<script>
const nursyslicensemismatchbucket = ["nursyslicensemismatch"];

        $(document).on('submit', '#orientationForm,#nursyslicensemismatchform', function(e) {
            e.preventDefault();
            var sData = $("#orientationForm,#nursyslicensemismatchform").serializeArray();


            for(var i=0; i < nursyslicensemismatchbucket.length;i++) {
                var tempObject = nursyslicensemismatchbucket[i].replace(/ /g,"_");
                if($("#reload-"+tempObject).val()==1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    
                    $.ajax({
                        url: "{{route('nursys.reloadnursysmismatchdata')}}?type="+nursyslicensemismatchbucket[i],
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
                   $("#dataTable-nursys-"+tempObject).DataTable().ajax.reload();
                }
            }

        });
        
</script>
