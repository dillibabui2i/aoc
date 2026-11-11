<?php
use App\Helpers\H;
use App\Helpers\LD;

$clientmatch = [
    [
        'label'=>'Me Mode',
        'name'=>'clientmatchmemode',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'clientmatchstate',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Region',
        'name'=>'region',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],[
        'label'=>'Branch',
        'name'=>'clientmatchbranch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'Client Care Coordinator',
        'name'=>'clientmatchclientcare',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
    ]
];

if (LD::checkFeatureFlag('payer-filters-on-client-match')) {
    $clientmatch[] = [
        'label'=>'Payer',
        'name'=>'Payer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayers()
    ];
    $clientmatch[] = [
        'label' => 'Special Payer Group',
        'name' => 'payer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ];
}

$clientmatch[] = [
    'label'=>'Clinician Start Date',
    'name'=>'client_start_date',
    'type'=>'dateRangePicker',
    'fromName'=>'fromClientStartDate',
    'toName'=>'toClientStartDate',
    'fromdate'=>'',
    'todate'=>'',
];


if (LD::checkFeatureFlag('ccc-client-match-bucket')) { ?>

    <div class="card-body">
        <div class="card">
            <div class="card-header" id="dailyactivityone">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                        data-target="#ccc_client_match" aria-expanded="false" aria-controls="collapseOne">
                        client match <span id="ccc_client_match-counter"></span><i class="fa fa-chevron-right"></i>
                    </button>
                </h4>
            </div>
        
            <div id="ccc_client_match" class="collapse" aria-labelledby="dailyactivityone"  data-parent="#client_care_childs">
                <p style="text-align: left;">
                    <u><b>Responsible Party:</b> Client Care Coordinator</u><br>
                    <b>Description:</b> For each match, first call the client to introduce yourself and/or review protocols (such as vacations, hospitalizations) and establish yourself as the client's point of contact with Angels of Care. Next, call the clinician to introduce yourself and/or review protocols (such as PTO, vacations, hospitalizations, establish their point of contact). Then, complete the client match by utilizing the outcome icon.<br>
                    <b>Timeline:</b> As they occur on this list for your branch - review daily at a minimum.
                </p>
                <div class="filters-common">
                    @component('components.datatable-filters',['filters'=>$clientmatch,'formname'=>'cccclientmatchform'])
                    @endcomponent
                </div>
                <div class="card-body">
                <?php echo H::getHelper("ClientMatch")->getDataTableHtml('ccc_client_match')?>
                </div>
            </div>
        </div>
    </div>

    <script>
       const cccClientMatch = ['ccc_client_match'];

    $(document).on('submit', '#cccclientmatchform', function(e) {
        e.preventDefault();
        var serializeData = $("#cccclientmatchform").serializeArray();
        //console.log(serializeData);
        for(var i=0; i < cccClientMatch.length;i++) {
            var tempObject = cccClientMatch[i].replace(/ /g,"_");
            if($("#reload-"+tempObject).val()==1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('ccc.reloadclientmatch') }}?type="+cccClientMatch[i],
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
                $("#dataTable-clientcare-exception-"+tempObject).DataTable().ajax.reload();
            }
        }
    });
    $("#cccclientmatchform").trigger("submit"); 


        $(document).on("change",'#clientmatchstate',function(){
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
                   $('#clientmatchbranch').empty();
                   $.each(response, function( index, value ) {
                       $("#clientmatchbranch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#clientmatchbranch").multiselect("rebuild");
               },
           });
        });

        $(document).on("click",'#clientmatchmemode',function(){ 
            $("#clientmatchclientcare").multiselect("clearSelection");
        });
        $(document).on("change",'#clientmatchclientcare',function(){ 
            $('#clientmatchmemode').prop('checked', false);
        });
        $('#clientmatchstate').change(function(){
            if ($(this).val().indexOf('TX') !== -1) {
                $('#cccclientmatchform select[name="region"]').multiselect('enable');
            } else {
                $('#cccclientmatchform select[name="region"]').multiselect('disable');
            }
        })
        $('#clientmatchstate .clearall').click(function() {
            $('#clientmatchstate sleect[name="region"]').multiselect('disable');
        });
    </script>
<?php } ?>