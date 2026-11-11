<?php
use App\Helpers\H;
use App\Helpers\LD;

$schedulesFilter = [
    [
        'label'=>'Me Mode',
        'name'=>'hourComparisonMeMode',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'hourComparisonState',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Branch',
        'name'=>'hourComparisonBranch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'Coordinator',
        'name'=>'hourComparisonCccId',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
    ],[
        'label'=>'Payer',
        'name'=>'hourComparisonPayer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayers()
    ],[
        'label' => 'Special Payer Group',
        'name' => 'hourComparisonPayerGroup',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ]
];
$buckets =H::getHelper("ClientCareCoordinatorHourComparison")->getBucketArray();
$keys = array_keys($buckets);
 ?>
    <div class="card-body">
        <div class="card">
            <div class="card-header" id="dailyactivityone">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                        data-target="#ccc_hour_comparison-container" aria-expanded="false" aria-controls="collapseOne">
                        Hours Comparison Bucket<span id="ccc_hour_comparison1-counter"></span><i class="fa fa-chevron-right"></i>
                    </button>
                </h4>
            </div>
            <div id="ccc_hour_comparison-container" class="collapse" aria-labelledby="dailyactivityone"
                data-parent="#client_care_childs">
                <div class="filters-common">
                    @component('components.datatable-filters',['filters'=>$schedulesFilter,'formname'=>'cccHourComparisonFilterForm'])
                    @endcomponent
                </div>

                <div class="card-body">
                    <?php
                        $buckets =  H::getHelper("ClientCareCoordinatorHourComparison")->getBuckets();
                        foreach($buckets as $bucketKey => $bucket) {?>
                            <div class="card">
                                <div class="card-header" id="ccc_hour_comparison-<?php echo $bucketKey?>">
                                    <h4 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button"
                                            data-toggle="collapse" data-target="#<?php echo $bucketKey?>" aria-expanded="false"
                                            aria-controls="collapseOne">
                                            <?php echo $bucket['heading']?> <span id="<?php echo $bucketKey?>-counter"></span> <i class="fa fa-chevron-right"></i>
                                        </button>
                                    </h4>
                                </div>
                                <div id="<?php echo $bucketKey?>" class="collapse " aria-labelledby="dailyactivityone"  data-parent="#ccc_hour_comparison-container">

                                    <div class="card-body">
                                    <?php  echo H::getHelper("ClientCareCoordinatorHourComparison")->getDataTableHtml($bucketKey)?>
                                    </div>
                                </div>
                            </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    const cccHourComparisonBucketArray = <?php echo json_encode($keys); ?>;
    $(document).on('submit', '#cccHourComparisonFilterForm', function(e) {
        e.preventDefault();
        var serializeData = $("#cccHourComparisonFilterForm").serializeArray();

        for(var i=0; i < cccHourComparisonBucketArray.length;i++) {
            var tempObject = cccHourComparisonBucketArray[i].replace(/ /g,"_");
            if($("#reload-"+tempObject).val()==1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('ccc.reloadClientCareCoordinatorHourComparison') }}?type="+cccHourComparisonBucketArray[i],
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
                $("#dataTable-clientcare-hour-comparison-"+tempObject).DataTable().ajax.reload();
            }
        }
    });
    $("#cccHourComparisonFilterForm").trigger("submit");
        $(document).on("change",'#hourComparisonState',function(){
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
                   $('#hourComparisonBranch').empty();
                   $.each(response, function( index, value ) {
                       $("#hourComparisonBranch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#hourComparisonBranch").multiselect("rebuild");
               },
           });
        });

        $(document).on("click",'#hourComparisonMeMode',function(){
            $("#hourComparisonCccId").multiselect("clearSelection");
        });
        $(document).on("change",'#hourComparisonCccId',function(){
            $('#hourComparisonMeMode').prop('checked', false);
        });
    </script>
