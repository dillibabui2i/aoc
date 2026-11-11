<?php
use App\Helpers\H;
use App\Helpers\LD;

$typedata = [
    ['value' => 'office', 'text' => 'Office'],
    ['value' => 'field', 'text' => 'Field'],
    ['value' => 'both', 'text' => 'Both']
];
$ocFilter = [
    'label'=>'Orientation Coordinator',
    'name'=>'oc',
    'type'=>'multiselect',
    'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('oc')
];
if(LD::checkFeatureFlag('cc-renewable-change-filter-column')) {
    $ocFilter = [
        'label'=>'Client Care Coordinator',
        'name'=>'ccc',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
    ];
}
$cccrenewablefilter = [
    [
        'label'=>'Me Mode',
        'name'=>'cccexpiring_default',
        'type'=>'checkbox',
        'value'=>true
    ],
    
    [
        'label'=>'State',
        'name'=>'cccexpiring_state',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('ocstate')
    ],[
        'label'=>'Region',
        'name'=>'cccexpiring_region',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],[
        'label'=>'Branch',
        'name'=>'cccexpiring_ipea_location_id',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('ipea_location_id')
    ],$ocFilter,[
        'label'=>'Clinician status',
        'name'=>'renewable_clinicianstatus',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRenewableClinicianStatus()
    ],[
        'label'=>'Renewable Item',
        'name'=>'renewable_items',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRenewableItems()
    ],
    [
        'label'=>'Expiration Date',
        'name'=>'cccexpiring_renewable_expiration_date',
        'type'=>'dateRangePicker',
        'fromName'=>'cccexpiring_fromrenewable_expiration_date',
        'toName'=>'cccexpiring_torenewable_expiration_date',
        'fromdate'=>'',
        'todate'=>'',
    ]

];
if(LD::checkFeatureFlag('add-type-field-and-filter-in-renewables-all-bucket')){
    $cccrenewablefilter[] = [
        'label'=>'Type',
        'name'=>'type',
        'type'=>'select',
        'source'=> $typedata
    ];
}
    $cccBucketKey = 'cccfifteenzeroexpiring';
?>
 <div class="card-body">
    
    <div class="card">
        <div class="card-header" id="dailyactivityone">
            <h4 class="mb-0">
                <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                    data-target="#<?php echo $cccBucketKey?>" aria-expanded="false" aria-controls="collapseOne">
                    <?php echo (LD::checkFeatureFlag('change-ccc-renewable-bucket-name')) ? '0-15 Days & Expired Renewables' : '15-0 Expiring Renewable'; ?> <span id="<?php echo $cccBucketKey?>-counter"></span> <i class="fa fa-chevron-right"></i>
                </button>
                <div class="col-md-2 text-right pull-left"><a href="{{route('renewables.showAll')}}" class="dashboard-showall" title="Show All Renewables">Show All Renewables</a></div>
            </h4>
        </div>

        <div id="<?php echo $cccBucketKey?>" class="collapse " aria-labelledby="dailyactivityone"  data-parent="#client_care_childs">

                @if(LD::checkFeatureFlag('cc-renewable-change-filter-column'))
                <p style="text-align: left;"><u><b>Responsible Party:</b> Client Care Coordinator</u><br>
                @else
                <p style="text-align: left;"><u><b>Responsible Party:</b> Client Care Manager</u><br>
                @endif
                <b>Description:</b> This bucket includes clinicians who have renewables entered in their KanTime clinician profile with a true expiration date for that item that expires in 0-15 days. This bucket should be monitored daily to ensure compliance with agency renewable policies. Clinicians will also get notifications via email when their renewables are expiring.<br>
                <div class="filters-common">
                    @component('components.datatable-filters', ['filters' => $cccrenewablefilter, 'formname' => 'cccrenewableform'])
                    @endcomponent
                </div>
            <?php 
            
                echo H::getHelper("Renewables")->getDataTableHtml($cccBucketKey);
                ?>
            
        </div>
    </div>
</div>

<script>
        const cccRenewablesBucket = ["cccfifteenzeroexpiring"];

        $(document).on('submit', '#cccrenewableform', function(e) {
            e.preventDefault();
            var sData = $("#cccrenewableform").serializeArray();

            console.log(sData);
            for(var i=0; i < cccRenewablesBucket.length;i++) {
                var tempObject = cccRenewablesBucket[i].replace(/ /g,"_");
                if($("#reload-"+tempObject).val()==1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    console.log(sData);
                    $.ajax({
                        url: "{{route('renewables.reloadexpiringdata')}}?type="+cccRenewablesBucket[i],
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
                   $("#dataTable-expiring-"+tempObject).DataTable().ajax.reload();
                }
            }

        });
        $("#cccrenewableform").trigger("submit");

        $(document).on("change",'#cccexpiring_state',function(){
            var state = $(this).val().join(",");


            if (state.indexOf('Texas') !== -1) {
                $('select[name="cccexpiring_region"]').multiselect('enable');
            } else {
                $('select[name="cccexpiring_region"]').multiselect('disable');
            }
            let _token = $('meta[name="csrf-token"]').attr('content');

            $.ajax({
                url: "{{ route('getocstatebranch') }}",
                type: "GET",
                data: {
                    state: state,
                    _token: _token
                },
                success: function(response) {
                    $('#cccexpiring_ipea_location_id').empty();
                    $.each(response, function( index, value ) {
                        $("#cccexpiring_ipea_location_id").append('<option value="'+value.id+'">'+value.branch+'</option>')
                    });
                    $("#cccexpiring_ipea_location_id").multiselect("rebuild");
                },
            });
        });

        
</script>
