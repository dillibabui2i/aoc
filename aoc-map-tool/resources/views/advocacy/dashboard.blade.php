<?php
use App\Helpers\H;
use App\Helpers\LD;
$employmentStatusList = H::getHelper('ConstantHelper')->getEmploymentStatus();
$employmentStatus = [];
foreach($employmentStatusList as $key => $value) {
    $employmentStatus[] = ['value' => $value->EmploymentStatusID, 'text' => $value->EmploymentStatus];
}
$taskStatus =  H::getHelper('Advocacy')->getAdvocacyTaskStatus();

$filters = [
    [
        'label'=>'Me Mode',
        'name'=>'addefault',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'adstate',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('ocstate')
    ],[
        'label'=>'Region',
        'name'=>'adregion',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],[
        'label'=>'Branch',
        'name'=>'adbranch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('ipea_location_id')
    ],[
        'label'=>'Advocacy Specialist',
        'name'=>'adspecialist',
        'type'=>'multiselect',
        'source'=>H::getAdvocacySpecialist()
    ],
    [
        'label'=>'New Status',
        'name'=>'adstatus',
        'type'=>'multiselect',
        'source'=>$employmentStatus
    ]
    ,
    [
        'label'=>'Task Status',
        'name'=>'adtaskstatus',
        'type'=>'multiselect',
        'source'=>$taskStatus
    ]
];

 ?>
 <div class="card">
        <div class="card-header" id="advocacy_widget">
            <h2 class="mb-0">
                <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                    data-target="#advocacydashboard_childs" aria-expanded="false" aria-controls="collapseThree">
                    Advocacy<i class="fa fa-chevron-right"></i>
                </button>
                <div class="col-md-2 text-right pull-left"><a href="{{route('statusChangeRequest.showAll')}}" class="dashboard-showall" title="Show all Status Change Requests">Show all Status Change Requests</a></div>
            </h2>

        </div>
        <div id="advocacydashboard_childs" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
            <div class="card-body">
                    <div class="card">
                        <div class="card-header" id="dailyactivityone">
                            <h4 class="mb-0">
                                <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                                    data-target="#advocacy_statuschangerequest" aria-expanded="false" aria-controls="collapseOne">
                                    Status Change Requests <span id="advocacy_statuschangerequest-counter"></span><i class="fa fa-chevron-right"></i>
                                </button>

                            </h4>
                        </div>
                        <div id="advocacy_statuschangerequest" class="collapse" aria-labelledby="dailyactivityone" data-parent="#advocacydashboard_childs">

                            <p style="text-align: left;"><u><b>Responsible Party:</b> Advocacy Specialist </u><br>
                                <b>Description:</b> Clinicians who have been requested to have the employment status changed. Please ensure that all necessary steps are taken before changing statuses in KanTime. Prompt action is essential in maintaining compliance and ensuring the continuity of client services.<br>
                                <b>Timeline:</b> Actions are to be taken within 1 hour of request.</p>

                            @component('components.datatable-filters',['filters'=>$filters,"formname"=>'advocacystatuschange'])
                                @endcomponent
                            <div class="card-body">

                                <?php echo H::getHelper("Advocacy")->getDataTableHtml('advocacy_statuschangerequest') ?>
                            </div>
                        </div>
                    </div>
            </div>


        </div>
    </div>
    <script>
        const advocacyWidget = ['advocacy_statuschangerequest'];
        $(document).on('submit', '#advocacystatuschange', function(e) {
            e.preventDefault();
            for(var i=0; i < advocacyWidget.length;i++) {
                var tempObject = advocacyWidget[i].replace(/ /g,"_");
                if($("#reload-"+tempObject).val()==1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('advocacy.reloadstatuschangerequest') }}?type="+advocacyWidget[i],
                        type: "POST",
                        data: {
                            _token: _token,
                            filters:$('#advocacystatuschange').serializeArray()
                        },
                        success: function(response) {
                            var jsonObject = JSON.parse(response);
                            var type = jsonObject.type;
                            var idObject = type.replace(/ /g,"_");
                            $("#"+idObject+'-counter').html(jsonObject.counter);
                        },
                    });
                } else {
                    $("#dataTable-advocacy-"+tempObject).DataTable().ajax.reload();
                }
            }
        });
        $("#advocacystatuschange").trigger("submit");


        $(document).on("change",'#adstate',function(){
            var state = $(this).val().join(",");
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
               url: "{{ route('getocstatebranch') }}",
               type: "GET",
               data: {
                   state: state,
                   _token: _token
               },
               success: function(response) {
                   $('#adbranch').empty();
                   $.each(response, function( index, value ) {
                       $("#adbranch").append('<option value="'+value.id+'">'+value.branch+'</option>')
                   });
                   $("#adbranch").multiselect("rebuild");
               },
           });

           if (state.indexOf('Texas') !== -1) {
                $('select[name="adregion"]').multiselect('enable');
            } else {
                $('select[name="adregion"]').multiselect('disable');
            }
        });

     </script>
