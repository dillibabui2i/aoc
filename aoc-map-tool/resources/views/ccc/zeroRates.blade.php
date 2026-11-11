<?php
use App\Helpers\H;
use App\Helpers\LD;

$cccZeroRatesFilter = [
    [
        'label'=>'Me Mode',
        'name'=>'zero_rates_memode',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'zero_rates_state',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Region',
        'name'=>'zero_rates_region',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],[
        'label'=>'Branch',
        'name'=>'zero_rates_branch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'Client Care Coordinator',
        'name'=>'zero_rates_clientcare',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
],
[
        'label'=>'Payer',
        'name'=>'zero_rates_payer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayers()
],
[
        'label' => 'Special Payer Group',
        'name' => 'zero_rates_payer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ],
    [
        'label' => 'Discipline',
        'name' => 'Discipline',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getDisciplineForZeroRates()
    ],
];

?>


    <div class="card-body">
        <div class="card">
            <div class="card-header" id="dailyactivityone">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                        data-target="#zero_rates" aria-expanded="false" aria-controls="collapseOne">
                        Zero Rates <span id="zero_rates-counter"></span><i class="fa fa-chevron-right"></i>
                    </button>
                </h4>
            </div>

            <div id="zero_rates" class="collapse" aria-labelledby="dailyactivityone"  data-parent="#client_care_childs">
                <p style="text-align: left;">
                    <b>Description:</b> Displays clinician rates that require correction in KanTime because the current rate is $0.<br>
                    <b>Timeline:</b> The Zero Rates bucket should be reviewed and cleared daily. Items will turn yellow when the rate has been marked as corrected in the App, but KanTime still reflects a $0 rate.
                </p>
                <div class="filters-common">
                    @component('components.datatable-filters',
                    ['filters'=>$cccZeroRatesFilter,'formname'=>'ccczeroratesform'])
                    @endcomponent
                </div>
                <div class="card-body">
                <?php echo H::getHelper("ClientCareCoordinatorCoverage")->getZeroRateDataTableHtml('zero_rates')?>
                </div>
            </div>
        </div>
    </div>

    <script>
       const cccZeroRates = ['zero_rates'];

    $(document).on('submit', '#ccczeroratesform', function(e) {
        e.preventDefault();
        var serializeData = $("#ccczeroratesform").serializeArray();
        //console.log(serializeData);
        for(var i=0; i < cccZeroRates.length;i++) {
            var tempObject = cccZeroRates[i].replace(/ /g,"_");
            if($("#reload-"+tempObject).val()==1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('ccc.reloadZeroRatesData') }}?type="+cccZeroRates[i],
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
    $("#ccczeroratesform").trigger("submit");


        $(document).on("change",'#zero_rates_state',function(){
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
                   $('#zero_rates_branch').empty();
                   $.each(response, function( index, value ) {
                       $("#zero_rates_branch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#zero_rates_branch").multiselect("rebuild");
               },
           });
        });

        $(document).on("click",'#zero_rates_memode',function() {
            $("#zero_rates_clientcare").multiselect("clearSelection");
        });
        $(document).on("change",'#zero_rates_clientcare',function() {
            $('#zero_rates_memode').prop('checked', false);
        });
        $('#zero_rates_state').change(function(){
            if ($(this).val().indexOf('TX') !== -1) {
                $('#ccczeroratesform select[name="zero_rates_region"]').multiselect('enable');
            } else {
                $('#ccczeroratesform select[name="zero_rates_region"]').multiselect('disable');
            }
        })
        $('#zero_rates_state .clearall').click(function() {
            $('#zero_rates_state sleect[name="zero_rates_region"]').multiselect('disable');
        });
    </script>
