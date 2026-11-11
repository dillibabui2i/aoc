<?php
use App\Helpers\H;
use App\Helpers\LD;


$cccActivveCoverageFilter = [
    [
        'label'=>'Me Mode',
        'name'=>'active_coverage_memode',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'active_coverage_state',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Region',
        'name'=>'active_coverage_region',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],[
        'label'=>'Branch',
        'name'=>'active_coverage_branch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'Coverage Coordinator',
        'name'=>'active_coverage_clientcare',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getCoverageCoordinatorList()
    ]
];

?>


    <div class="card-body">
        <div class="card">
            <div class="card-header" id="dailyactivityone">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                        data-target="#ccc_active_coverage" aria-expanded="false" aria-controls="collapseOne">
                        Active Coverage <span id="ccc_active_coverage-counter"></span><i class="fa fa-chevron-right"></i>
                    </button>
                </h4>
            </div>
        
            <div id="ccc_active_coverage" class="collapse" aria-labelledby="dailyactivityone"  data-parent="#client_care_childs">
                <p style="text-align: left;">
                    <u><b>Responsible Party:</b> Coverage Coordinator</u><br>
                    <b>Description:</b> Document days Active Coverage clinicians are available to cover shifts.<br>
                    <b>Timeline:</b> Contact and get schedule for the next month by the 10th of the current month. If there is no contact by the 15th, then escalate to manager.<br>
                    
                </p>
                <div class="filters-common">
                    @component('components.datatable-filters',
                    ['filters'=>$cccActivveCoverageFilter,'formname'=>'cccactivecoverageform'])
                    @endcomponent
                </div>
                <div class="card-body">
                <?php echo H::getHelper("ClientCareCoordinatorCoverage")->getActiveCoverageDataTableHtml('ccc_active_coverage')?>
                </div>
            </div>
        </div>
    </div>

    <script>
       const activecoverage = ['ccc_active_coverage'];

    $(document).on('submit', '#cccactivecoverageform', function(e) {
        e.preventDefault();
        var serializeData = $("#cccactivecoverageform").serializeArray();
        //console.log(serializeData);
        for(var i=0; i < activecoverage.length;i++) {
            var tempObject = activecoverage[i].replace(/ /g,"_");
            if($("#reload-"+tempObject).val()==1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('ccc.reloadactivecoveragedata') }}?type="+activecoverage[i],
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
    $("#cccactivecoverageform").trigger("submit");


        $(document).on("change",'#active_coverage_state',function(){
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
                   $('#active_coverage_branch').empty();
                   $.each(response, function( index, value ) {
                       $("#active_coverage_branch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#active_coverage_branch").multiselect("rebuild");
               },
           });
        });

        
        $('#active_coverage_state').change(function(){
            if ($(this).val().indexOf('TX') !== -1) {
                $('#cccactivecoverageform select[name="active_coverage_region"]').multiselect('enable');
            } else {
                $('#cccactivecoverageform select[name="active_coverage_region"]').multiselect('disable');
            }
        })
        $('#active_coverage_state .clearall').click(function() {
            $('#active_coverage_state select[name="active_coverage_region"]').multiselect('disable');
        });
    </script>