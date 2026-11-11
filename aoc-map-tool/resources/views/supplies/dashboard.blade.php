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
?>
<div class="card">
    <div class="card-header" id="supplyRequest">
        <h2 class="mb-0">
            <button
            class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                data-target="#supplyRequest_childs" aria-expanded="false" aria-controls="collapseThree">
                Supply Request<i class="fa fa-chevron-right"></i>
            </button>
            <div class="col-md-2 text-right pull-left p-0">
                <a href="{{route('supplies.list')}}"
                class="dashboard-showall"
                title="Show All Supply Requests">
                    Show All Supply Requests
                </a>
            </div>
        </h2>
    </div>
    <div id="supplyRequest_childs" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
        <div class="card-body">
            <div class="card">
                <div class="card-header" id="dailyactivityone">
                    <h4 class="mb-0">
                        <button
                        class="btn btn-link btn-block text-left collapsed col-md-10 pull-left"
                        type="button" data-toggle="collapse"
                            data-target="#supply_requests_new" aria-expanded="false" aria-controls="collapseOne">
                            Supply Requests
                            <span id="supply_requests_new-counter"></span>
                            <i class="fa fa-chevron-right"></i>
                        </button>
                    </h4>
                </div>
                <div id="supply_requests_new"
                class="collapse" aria-labelledby="dailyactivityone" data-parent="#supplyRequest_childs">
                    <div class="filters-common" style="display: flex;">
                        <div class="pt-3">
                            <a href="javascript:void(0);" onclick="addSuppliesRequest();">
                                <i class="fa fa-plus-circle"
                                aria-hidden="true"
                                style="font-size:28px;color:#2f5baa;"></i>
                            </a>
                        </div>
                        @component(
                            'components.datatable-filters',['filters'=>$supplyRequestsFilter,
                            'formname'=>'supplyrequestsform'])
                        @endcomponent
                    </div>
                    <div class="card-body">
                        <?php  echo H::getHelper("SupplyRequests")->getDataTableHtml('supply_requests_new')?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('supplies.deletepopup')
<x-modal>
        <x-slot name="id">acknowledgeRequest</x-slot>
        <x-slot name="size">w-50</x-slot>
        <x-slot name="title">Acknowledge Receipt</x-slot>
        <x-slot name="class"></x-slot>
        <x-slot name="body">
            <h5>Acknowledge you have seen this supply request</h5>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-primary" id="acknowledgeRequestConfirmationBtn" data-id="">Yes</button>
            <button type="button" class="btn btn-primary" data-dismiss="modal">No</button>
        </x-slot>
    </x-modal>
    <script>
    const supplyRequestsBucket = 'supply_requests_new';
    $(document).on('submit', '#supplyrequestsform', function(e) {
        e.preventDefault();
        var serializeData = $("#supplyrequestsform").serializeArray();
        var tempObject = supplyRequestsBucket.replace(/ /g,"_");
        if($("#reload-"+tempObject).val()==1) {
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{ route('supplies.reloadCountForSupplyRequests') }}?type="+supplyRequestsBucket,
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
    $("#supplyrequestsform").trigger("submit");

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
    function acknowledgeRequest(id){
        $('#acknowledgeRequest').modal('show');
        $('#acknowledgeRequestConfirmationBtn').attr('data-id',id);
    }
    $(document).on('click', '#acknowledgeRequestConfirmationBtn', function(e) {
        var id = $(this).attr('data-id');
        if(id != ''){
            acknowledgSupplyRequests(id);
        }

    });
    function acknowledgSupplyRequests(id){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('supplies.acknowledgeSupplyRequests')}}",
            type: 'get',
            dateType:"html",
            data: {
                _token: _token,
                id: id
            },
            success: function(data) {
                $('#acknowledgeRequest').modal('hide');
                if(data.status){
                    $('.dataTable').DataTable().ajax.reload(null, false);
                }
            }
        });
    }
    </script>
