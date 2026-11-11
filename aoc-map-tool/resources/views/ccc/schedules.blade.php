<?php
use App\Helpers\H;
use App\Helpers\LD;

$schedulesFilter = [
    [
        'label'=>'Me Mode',
        'name'=>'scheduleMeMode',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'scheduleState',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Branch',
        'name'=>'scheduleBranch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'Coordinator',
        'name'=>'scheduleCccId',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
    ],[
        'label'=>'Payer',
        'name'=>'schedulePayer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayers()
    ],[
        'label' => 'Special Payer Group',
        'name' => 'schedulePayerGroup',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ]
];
$buckets =H::getHelper("ClientCareCoordinatorSchedules")->getBucketArray();
$keys = array_keys($buckets);
 ?>
    <div class="card-body">
        <div class="card">
            <div class="card-header" id="dailyactivityone">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                        data-target="#ccc_schedules-container" aria-expanded="false" aria-controls="collapseOne">
                        Schedules<span id="ccc_schedules1-counter"></span><i class="fa fa-chevron-right"></i>
                    </button>
                </h4>
            </div>
            <div id="ccc_schedules-container" class="collapse" aria-labelledby="dailyactivityone"
                data-parent="#client_care_childs">
                <div class="filters-common">
                    @component('components.datatable-filters',['filters'=>$schedulesFilter,'formname'=>'cccSchedulesFilterForm'])
                    @endcomponent
                </div>

                <div class="card-body">
                    <?php
                        $buckets =  H::getHelper("ClientCareCoordinatorSchedules")->getBuckets();
                        foreach($buckets as $bucketKey => $bucket) {?>
                            <div class="card">
                                <div class="card-header" id="ccc_schedules-<?php echo $bucketKey?>">
                                    <h4 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button"
                                            data-toggle="collapse" data-target="#<?php echo $bucketKey?>" aria-expanded="false"
                                            aria-controls="collapseOne">
                                            <?php echo $bucket['heading']?> <span id="<?php echo $bucketKey?>-counter"></span> <i class="fa fa-chevron-right"></i>
                                        </button>
                                    </h4>
                                </div>
                                <div id="<?php echo $bucketKey?>" class="collapse " aria-labelledby="dailyactivityone"  data-parent="#ccc_schedules-container">

                                    <div class="card-body">
                                    <?php  echo H::getHelper("ClientCareCoordinatorSchedules")->getDataTableHtml($bucketKey)?>
                                    </div>
                                </div>
                            </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    const cccScheduleBucketArray = <?php echo json_encode($keys); ?>;
    $(document).on('submit', '#cccSchedulesFilterForm', function(e) {
        e.preventDefault();
        var serializeData = $("#cccSchedulesFilterForm").serializeArray();

        for(var i=0; i < cccScheduleBucketArray.length;i++) {
            var tempObject = cccScheduleBucketArray[i].replace(/ /g,"_");
            if($("#reload-"+tempObject).val()==1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('ccc.reloadClientCareCoordinatorSchedules') }}?type="+cccScheduleBucketArray[i],
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
                $("#dataTable-clientcare-schedule-"+tempObject).DataTable().ajax.reload();
            }
        }
    });
    $("#cccSchedulesFilterForm").trigger("submit");
        $(document).on("change",'#scheduleState',function(){
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
                   $('#scheduleBranch').empty();
                   $.each(response, function( index, value ) {
                       $("#scheduleBranch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#scheduleBranch").multiselect("rebuild");
               },
           });
        });

        $(document).on("click",'#scheduleMeMode',function(){
            $("#scheduleCccId").multiselect("clearSelection");
        });
        $(document).on("change",'#scheduleCccId',function(){
            $('#scheduleMeMode').prop('checked', false);
        });
    </script>
