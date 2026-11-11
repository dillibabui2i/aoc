<?php

use App\Helpers\H;
use App\Helpers\LD;


if(LD::checkFeatureFlag('region-filter-for-all-dashboard'))  {

$intakeClients = [
    [
        'label' => 'Me Mode',
        'name' => 'intakememode',
        'type' => 'checkbox',
        'value' => true
    ],
    [
        'label' => 'State',
        'name' => 'intakestate',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ], 
    [
        'label'=>'Region',
        'name'=>'intakeregion',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label' => 'Branch',
        'name' => 'intakebranch',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ], [
        'label' => 'Client Care Coordinator',
        'name' => 'intakeclientcare',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
    ], [
        'label' => 'Payer',
        'name' => 'intakepayer',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayers()
    ], [
        'label' => 'Special Payer Group',
        'name' => 'intakepayer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ]
];
} else {

    $intakeClients = [
    [
        'label' => 'Me Mode',
        'name' => 'intakememode',
        'type' => 'checkbox',
        'value' => true
    ],
    [
        'label' => 'State',
        'name' => 'intakestate',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ], 
    [
        'label' => 'Branch',
        'name' => 'intakebranch',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ], [
        'label' => 'Client Care Coordinator',
        'name' => 'intakeclientcare',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
    ], [
        'label' => 'Payer',
        'name' => 'intakepayer',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayers()
    ], [
        'label' => 'Special Payer Group',
        'name' => 'intakepayer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ]
];
}

?>

<div class="card-body">
    <div class="card">
        <div class="card-header" id="dailyactivityone">
            <h4 class="mb-0">
                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#ccc_intake_clients-container" aria-expanded="false" aria-controls="collapseOne">
                    Intake Clients <span id="ccc_intake_clients-counter"></span><i class="fa fa-chevron-right"></i>
                </button>
            </h4>
        </div>



        <div id="ccc_intake_clients-container" class="collapse" aria-labelledby="dailyactivityone" data-parent="#client_care_childs">
            <div class="filters-common">
                @component('components.datatable-filters',['filters'=>$intakeClients,'formname'=>'cccintakeclientsform'])
                @endcomponent
            </div>

            <div class="card-body">
                <div class="card">
                    <div class="card-header" id="ccc-planned-visit-container">
                        <h4 class="mb-0">
                            <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#ccc_new_clients" aria-expanded="false" aria-controls="collapseOne">New Clients <span id="ccc_new_clients-counter"></span> <i class="fa fa-chevron-right"></i>
                            </button>
                        </h4>
                    </div>
                    <div id="ccc_new_clients" class="collapse " aria-labelledby="dailyactivityone" data-parent="#ccc_intake_clients-container">
                        <?php if(LD::checkFeatureFlag('new-intake-clients-modifications')) { ?>
                            <p style="text-align: left;">
                                <b>Description:</b> All non-therapy clients who have completed the SOC, are ready to start, and require an introductory call to introduce the CCC as their new primary point of contact.<br>
                            </p>
                        <?php } ?>

                        <div class="card-body">
                            <?php echo H::getHelper("ClientCareCoordinatorException")->getDataTableHtml('ccc_new_clients') ?>
                        </div>
                    </div>
                </div>
            </div>

            @if(LD::checkFeatureFlag('hide-ccc-pending-intake-clients'))
                <div class="card-body">
                    <div class="card">
                        <div class="card-header" id="ccc-planned-visit-container">
                            <h4 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#ccc_pending_intake_clients" aria-expanded="false" aria-controls="collapseOne">Pending Intake Clients <span id="ccc_pending_intake_clients-counter"></span> <i class="fa fa-chevron-right"></i>
                                </button>
                            </h4>
                        </div>
                        <div id="ccc_pending_intake_clients" class="collapse " aria-labelledby="dailyactivityone" data-parent="#ccc_intake_clients-container">
                            <div class="card-body">
                                <?php echo H::getHelper("ClientCareCoordinatorException")->getDataTableHtml('ccc_pending_intake_clients') ?>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(LD::checkFeatureFlag('ccc-intake-coverage-shifts-booked-bucket'))
                <div class="card-body">
                    <div class="card">
                        <div class="card-header" id="ccc-coverage-shifts-booked">
                            <h4 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#ccc_intake_coverage_shifts_booked" aria-expanded="false" aria-controls="collapseOne">Coverage Shifts Booked <span id="ccc_intake_coverage_shifts_booked-counter"></span> <i class="fa fa-chevron-right"></i>
                                </button>
                            </h4>
                        </div>
                        <div id="ccc_intake_coverage_shifts_booked" class="collapse " aria-labelledby="dailyactivityone" data-parent="#ccc_intake_clients-container">
                            <div class="card-body">
                                <?php echo H::getHelper("ClientCareCoordinatorException")->getDataTableHtml('ccc_intake_coverage_shifts_booked') ?>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    const intakeClientsBucket = ['ccc_new_clients', 'ccc_pending_intake_clients', 'ccc_intake_coverage_shifts_booked'];

    $(document).on('submit', '#cccintakeclientsform', function(e) {
        e.preventDefault();
        var serializeData = $("#cccintakeclientsform").serializeArray();

        for (var i = 0; i < intakeClientsBucket.length; i++) {
            var tempObject = intakeClientsBucket[i].replace(/ /g, "_");
            if ($("#reload-" + tempObject).val() == 1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('clientcare.reloadcountclientcareexceptions') }}?type=" + intakeClientsBucket[i],
                    type: "POST",
                    data: {
                        filters: serializeData,
                        _token: _token
                    },
                    success: function(response) {
                        var jsonObject = JSON.parse(response);
                        var type = jsonObject.type;
                        var idObject = type.replace(/ /g, "_");
                        $("#" + idObject + '-counter').html(jsonObject.counter);
                    },
                });
            } else {
                $("#dataTable-clientcare-exception-" + tempObject).DataTable().ajax.reload();
            }
        }
    });
    $("#cccintakeclientsform").trigger("submit");


    $(document).on("change", '#intakestate', function() {
        var state = $(this).val().join(",");
        let _token = $('meta[name="csrf-token"]').attr('content');

        <?php if(LD::checkFeatureFlag('region-filter-for-all-dashboard'))  { ?>
                if (state.indexOf('TX') !== -1) {
                    $('#intakeregion').multiselect('enable');
                } else {
                    $('#intakeregion').multiselect('disable');
                }
            <?php } ?>
        $.ajax({
            url: "{{ route('getstatebranch') }}",
            type: "GET",
            data: {
                state: state,
                _token: _token
            },
            success: function(response) {
                $('#intakebranch').empty();
                $.each(response, function(index, value) {
                    $("#intakebranch").append('<option value="' + value.id + '">' + value.FacilityName + '</option>')
                });
                $("#intakebranch").multiselect("rebuild");
            },
        });
    });

    $(document).on("click", '#intakememode', function() {
        $("#intakeclientcare").multiselect("clearSelection");
    });
    $(document).on("change", '#intakeclientcare', function() {
        $('#intakememode').prop('checked', false);
    });
</script>