<?php
    use App\Helpers\H;
    $openSchoolSetUpRequestsFilter = [
        [
            'label'=>'Me Mode',
            'name'=>'open_school_setup_requests_memode',
            'type'=>'checkbox',
            'value'=>true
        ],
        [
            'label'=>'State',
            'name'=>'open_school_setup_requests_state',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label'=>'Region',
            'name'=>'open_school_setup_requests_region',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],[
            'label'=>'Branch',
            'name'=>'open_school_setup_requests_aoc_facility_id',
            'type'=>'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
        ],[
            'label' => 'Client Care Coordinator',
            'name' => 'open_school_setup_requests_client_care_coordinator',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
        ],[
            'label'=>'Payer',
            'name'=>'open_school_setup_requests_payer',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getPayers()
        ],[
            'label' => 'Special Payer Group',
            'name' => 'open_school_setup_requests_payer_group',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayerGroup()
        ],
    ];
?>
<div class="card-header">
    <h4 class="mb-0">
        <button class="btn btn-link btn-block text-left collapsed col-md-9 pull-left" type="button"
        data-toggle="collapse" data-target="#open_school_setup_requests"
        aria-expanded="false" aria-controls="collapseOne">
            School Set Up Requests
            <span id="open_school_setup_requests-counter"></span><i class="fa fa-chevron-right"></i>
        </button>
    </h4>
</div>
<div id="open_school_setup_requests" class="collapse" data-parent="#school_setup_childs">
    <div class="filters-common">
        @component('components.datatable-filters',['filters'=>$openSchoolSetUpRequestsFilter,
        'formname'=>'open_school_setup_requests_form'])
        @endcomponent
    </div>
    <div class="card-body">
        <?php echo H::getHelper("SchoolSetup")->getDataTableHtml('open_school_setup_requests',
        '#open_school_setup_requests_form') ?>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        $(document).on('submit', '#open_school_setup_requests_form', function(e) {
            e.preventDefault();
            var serializeData = $("#open_school_setup_requests_form").serializeArray();
            let _token = $('meta[name="csrf-token"]').attr('content');
            var tempObject = 'open_school_setup_requests';
                if($("#reload-"+tempObject).val()==1) {
                    $.ajax({
                        url: "{{ route('dashboard.reloadOpenSchoolSetupRequest') }}?type=open_school_setup_requests",
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
        });
        $("#open_school_setup_requests_form").trigger("submit");
    });
    $(document).on("change", '#open_school_setup_requests_state', function () {
        var state = $(this).val().join(",");
        if (state.indexOf('TX') !== -1) {
            $('select[name="open_school_setup_requests_region"]').multiselect('enable');
        } else {
            $('select[name="open_school_setup_requests_region"]').multiselect('disable');
        }
        let _token = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: "{{ route('getstatebranch') }}",
            type: "GET",
            data: {
                state: state,
                _token: _token
            },
            success: function (response) {
                $('#open_school_setup_requests_aoc_facility_id').empty();
                $.each(response, function (index, value) {
                    $("#open_school_setup_requests_aoc_facility_id").append(
                        '<option value="' + value.id + '">' + value.FacilityName + '</option>')
                });
                $("#open_school_setup_requests_aoc_facility_id").multiselect("rebuild");
            },
        });
    });
</script>