<?php
use App\Helpers\H;
use App\Helpers\LD;

$patientStatus = H::getHelper("ConstantHelper")->getMssqlPatientStatus();
$status = [];
foreach($patientStatus as $ps){
    $status[] = ["value"=>$ps->id,'text'=>$ps->PatientStatus];
}
    $cccCaseLoadFilter = [
    [
        'label'=>'Me Mode',
        'name'=>'case_load_memode',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'case_load_state',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Region',
        'name'=>'case_load_region',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],[
        'label'=>'Branch',
        'name'=>'case_load_branch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'Client Care Coordinator',
        'name'=>'case_load_clientcare',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
    ],
    [
        'label'=>'Payer',
        'name'=>'case_load_payer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayers()
    ],
    [
        'label' => 'Special Payer Group',
        'name' => 'case_load_payer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ],
    [
        'label' => 'Status',
        'name' => 'case_load_CurrentStatusID',
        'type' => 'multiselect',
        'source' => $status,
        'default'=>1
    ],
];

?>


    <div class="card-body">
        <div class="card">
            <div class="card-header" id="dailyactivityone">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                        data-target="#case_load" aria-expanded="false" aria-controls="collapseOne">
                        Case Load <span id="case_load-counter"></span><i class="fa fa-chevron-right"></i>
                    </button>
                </h4>
            </div>
        
            <div id="case_load" class="collapse" aria-labelledby="dailyactivityone"  data-parent="#client_care_childs">
                <p style="text-align: left;">
                    <u><b>Responsible Party:</b> Client Care Coordinator</u><br>
                    <b>Description:</b> Overview of client’s assigned to client care coordinators.<br>
                    
                </p>
                <div class="filters-common">
                    @component('components.datatable-filters',
                    ['filters'=>$cccCaseLoadFilter,'formname'=>'ccccaseloadform'])
                    @endcomponent
                </div>
                <div class="card-body">
                <?php echo H::getHelper("ClientCareCoordinatorCoverage")->getCaseLoadDataTableHtml('case_load')?>
                </div>
            </div>
        </div>
    </div>

    <script>
    const cccCaseLoad = ['case_load'];
    var manualExecution = 0;
    function setStatusFilter() {
        $('#case_load_CurrentStatusID option').each(function() {
            if($(this).val()==1 || $(this).val()==7 )
                $(this).prop('selected', true);
        })
        $('#case_load_CurrentStatusID').multiselect('refresh');
        manualExecution++;
    }

    $(document).on('submit', '#ccccaseloadform', function(e) {
        e.preventDefault();
        <?php if(LD::checkFeatureFlag('caseload-bucket-additional-filters')) { ?> 
                if(!manualExecution)
                    setStatusFilter();
        <?php } ?>
        var serializeData = $("#ccccaseloadform").serializeArray();
        //console.log(serializeData);
        for(var i=0; i < cccCaseLoad.length;i++) {
            var tempObject = cccCaseLoad[i].replace(/ /g,"_");
            if($("#reload-"+tempObject).val()==1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('ccc.reloadcaseloaddata') }}?type="+cccCaseLoad[i],
                    type: "POST",
                    data: {
                        filters: serializeData,
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
                $("#dataTable-"+tempObject).DataTable().ajax.reload();
            }
        }
    });
    $("#ccccaseloadform").trigger("submit");


        $(document).on("change",'#case_load_state',function(){
            var state = $(this).val().join(",");
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
               url: "{{ route('getstatebranch') }}",
               type: "GET",
               data: {
                   state: state,
                   _token: _token
               },
               success: function(response) {
                   $('#case_load_branch').empty();
                   $.each(response, function( index, value ) {
                       $("#case_load_branch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#case_load_branch").multiselect("rebuild");
               },
           });
        });

        $(document).on("click",'#case_load_memode',function() {
            $("#case_load_clientcare").multiselect("clearSelection");
        });
        $(document).on("change",'#case_load_clientcare',function() {
            $('#case_load_memode').prop('checked', false);
        });
        $('#case_load_state').change(function(){
            if ($(this).val().indexOf('TX') !== -1) {
                $('#ccccaseloadform select[name="case_load_region"]').multiselect('enable');
            } else {
                $('#ccccaseloadform select[name="case_load_region"]').multiselect('disable');
            }
        })
        $('#case_load_state .clearall').click(function() {
            $('#case_load_state select[name="case_load_region"]').multiselect('disable');
        });
    </script>