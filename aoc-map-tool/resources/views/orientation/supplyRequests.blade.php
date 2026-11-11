<?php
use App\Helpers\H;
use App\Helpers\LD;

$ocList = [];
foreach (H::getOrientationCoordinatorList() as $key => $value) {
    $ocList[] = ['text'=>$value->name,'value'=>$value->id];
}

$supplyFilters = [
    [
        'label'=>'State',
        'name'=>'ocsupplystate',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],
    [
        'label'=>'Region',
        'name'=>'ocsupplyregion',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label'=>'Branch',
        'name'=>'ocsupplybranch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],
    [
        'label'=>'Orientation Coordinator',
        'name'=>'ocsupplycreatedby',
        'type'=>'multiselect',
        'source'=>$ocList
    ]
];

if (LD::checkFeatureFlag('oc-dashboard-supply-requests')) { ?>
    <div class="card-body">
        <div class="card">
            <div class="card-header" id="dailyactivityone">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed col-md-11 pull-left" type="button" data-toggle="collapse"
                        data-target="#oc_supply_requests-container" aria-expanded="false" aria-controls="collapseOne">
                        Supply Requests <span id="oc_supply_requests-counter"></span><i class="fa fa-chevron-right"></i>
                    </button>
                    <!-- <div class="col-md-1 text-right pull-left p-0"><a href="{{route('supplies.list')}}" class="dashboard-showall">Show All</a></div> -->
                </h4>
            </div>

            <div id="oc_supply_requests-container" class="collapse" aria-labelledby="dailyactivityone"
                data-parent="#orientation">
                <div class="filters-common">
                        @component('components.datatable-filters',['filters' => $supplyFilters,'formname'=>'oc_supply_requests_filter_form'])
                        @endcomponent
                </div>
                <div class="card-body">
                    <?php  echo H::getHelper("SupplyRequests")->getDataTableHtml('oc_supply_requests')?>
                </div>
            </div>
        </div>
    </div>

    <x-modal>
        <x-slot name="id">supplyFulFillRequestsConfirmationModal</x-slot>
        <x-slot name="size">w-50</x-slot>
        <x-slot name="title">Supply Request</x-slot>
        <x-slot name="class"></x-slot>
        <x-slot name="body">
            <h5>Please confirm you have fulfilled the supply request</h5>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-primary" id="supplyFulFillRequestsConfirmationBtn" data-id="">Confirm</button>
            <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
        </x-slot>
    </x-modal>
<?php } ?>

<script>
    const supplyBucket = ["oc_supply_requests"];
    $(document).on('submit', '#oc_supply_requests_filter_form', function(e) {
        e.preventDefault();
        for(var i=0; i < supplyBucket.length;i++) {
            var tempObject = supplyBucket[i].replace(/ /g,"_");
            $("#dataTable-supplyRequests-"+tempObject).DataTable().ajax.reload();
        }
    });
    $("#oc_supply_requests_filter_form").trigger("submit");

    function processSupplyRequests(id){
        $('#supplyFulFillRequestsConfirmationModal').modal('show');
        $('#supplyFulFillRequestsConfirmationBtn').attr('data-id',id);
    }
    $(document).on('click', '#supplyFulFillRequestsConfirmationBtn', function(e) {
        var id = $(this).attr('data-id');
        if(id != ''){
            fulfillSupplyRequests(id);
        }

    });
    function fulfillSupplyRequests(id){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('supplies.fulfillSupplyRequests')}}",
            type: 'get',
            dateType:"html",
            data: {
                _token: _token,
                id: id
            },
            success: function(data) {
                $('#supplyFulFillRequestsConfirmationModal').modal('hide');
                if(data.status){
                    $('.dataTable').DataTable().ajax.reload(null, false);
                }
            }
        });
    }


    $(document).on("change",'#ocsupplystate',function(){
                
                var state = $(this).val().join(",");

                <?php if(LD::checkFeatureFlag("region-filter-for-all-dashboard")) { ?> 
                        if (state.indexOf('TX') !== -1) {
                            $('select[name="ocsupplyregion"]').multiselect('enable');
                        } else {
                            $('select[name="ocsupplyregion"]').multiselect('disable');
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
                        $('#ocsupplybranch').empty();
                        $.each(response, function( index, value ) {
                            $("#ocsupplybranch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                        });
                        $("#ocsupplybranch").multiselect("rebuild");
                        
                    },
                });

        });
</script>
