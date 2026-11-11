<?php
use App\Helpers\LD;
use App\Helpers\H;
    $supplyRequestsFilter = [
        [
            'label'=>'State',
            'name'=>'ccsupplystate',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label'=>'Region',
            'name'=>'ccsupplyregion',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],
        [
            'label'=>'Branch',
            'name'=>'ccsupplybranch',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
        ],
        [
            'label'=>'Requested By',
            'name'=>'ccsupplycreatedby',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
        ],
        [
            'label'=>'Requested Date',
            'name'=>'requestedDate',
            'type'=>'dateRangePicker',
            'fromName'=>'fromRequestedDate',
            'toName'=>'toRequestedDate',
            'fromdate'=>'',
            'todate'=>'',
        ]
    ];


if (LD::checkFeatureFlag('supply-requests')) { ?>
    <div class="card-body">
        <div class="card">
            @if (LD::checkFeatureFlag('supply-requests-add-filters'))
            <div class="card-header" id="dailyactivityone">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed col-md-10 pull-left" type="button" data-toggle="collapse"
                        data-target="#supply_requests" aria-expanded="false" aria-controls="collapseOne">
                        Supply Requests <span id="supply_requests-counter"></span><i class="fa fa-chevron-right"></i>
                    </button>
                    <div class="col-md-2 text-right pull-left p-0"><a href="{{route('supplies.list')}}" class="dashboard-showall" title="Show All Supply Requests">Show All Supply Requests</a></div>
                </h4>
            </div>
            <div id="supply_requests" class="collapse" aria-labelledby="dailyactivityone" data-parent="#client_care_childs">
                <div class="filters-common" style="display: flex;">
                    <div class="pt-3">
                        <a href="javascript:void(0);" onclick="addSuppliesRequest();"><i class="fa fa-plus-circle" aria-hidden="true" style="font-size:28px;color:#2f5baa;"></i></a>
                    </div>
                    @component('components.datatable-filters',['filters'=>$supplyRequestsFilter,'formname'=>'cccsupplyrequestsform'])
                    @endcomponent
                </div>
                <div class="card-body">
                    <?php  echo H::getHelper("SupplyRequests")->getDataTableHtml('supply_requests')?>
                </div>
            </div>
            @else
            <div class="card-header" id="dailyactivityone">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed col-md-11 pull-left" type="button" data-toggle="collapse"
                        data-target="#supply_requests-container" aria-expanded="false" aria-controls="collapseOne">
                        Supply Requests <span id="supply_requests-counter"></span><i class="fa fa-chevron-right"></i>
                    </button>
                    <div class="col-md-1 text-right pull-left p-0"><a href="{{route('supplies.list')}}" class="dashboard-showall">Show All</a></div>
                </h4>
            </div>
            <div id="supply_requests-container" class="collapse" aria-labelledby="dailyactivityone" data-parent="#client_care_childs">
                <div class="card-body">
                    <?php  echo H::getHelper("SupplyRequests")->getDataTableHtml('supply_requests')?>
                </div>
            </div>
            @endif
        </div>
    </div>
    @if (LD::checkFeatureFlag('supply-requests-add-filters'))
    <script>
    const ccSupplyRequestsBucket = 'supply_requests';
    $(document).on('submit', '#cccsupplyrequestsform', function(e) {
        e.preventDefault();
        var serializeData = $("#cccsupplyrequestsform").serializeArray();
        var tempObject = ccSupplyRequestsBucket.replace(/ /g,"_");
        if($("#reload-"+tempObject).val()==1) {
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{ route('supplies.reloadCountForSupplyRequests') }}?type="+ccSupplyRequestsBucket,
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
            $("#dataTable-supplyRequests-"+tempObject).DataTable().ajax.reload();
        }
    });
    $("#cccsupplyrequestsform").trigger("submit"); 
    $(document).on("change",'#ccsupplystate',function(){
        var state = $(this).val().join(",");

        <?php if(LD::checkFeatureFlag("region-filter-for-all-dashboard")) { ?> 
                if (state.indexOf('TX') !== -1) {
                    $('select[name="ccsupplyregion"]').multiselect('enable');
                } else {
                    $('select[name="ccsupplyregion"]').multiselect('disable');
                }
        <?php } ?>

        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{ route('getstatebranch') }}",
            type: "GET",
            data: {
                state: state,
                _token: _token
            },
            success: function(response) {
                $('#ccsupplybranch').empty();
                $.each(response, function( index, value ) {
                    $("#ccsupplybranch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                });
                $("#ccsupplybranch").multiselect("rebuild");
            },
        });
    });
    </script>
    @endif
<?php } ?>
