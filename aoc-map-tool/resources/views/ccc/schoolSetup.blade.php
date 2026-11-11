<?php
use App\Helpers\H;

$schoolSetupFilter = [
    [
        'label' => 'Me Mode',
        'name' => 'ccc_school_setup_default',
        'type' => 'checkbox',
        'value' => true
    ],
    [
        'label' => 'State',
        'name' => 'ccc_school_setup_state',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],
    [
        'label' => 'Region',
        'name' => 'ccc_school_setup_region',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label' => 'Branch',
        'name' => 'ccc_school_setup_aoc_facility_id',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],
    [
        'label' => 'Client Care Coordinator',
        'name' => 'ccc_school_setup_client_care_coordinator',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
    ],
    [
        'label'=>'Payer',
        'name'=>'ccc_school_setup_payer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayers()
    ],[
        'label' => 'Special Payer Group',
        'name' => 'ccc_school_setup_payer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ],
];
    $cccClientNeedingSchoolSetUp = 'ccc_client_need_school_setup';
?>
<div class="card">
    <div class="card-header" id="dailyactivityone">
        <h4 class="mb-0">
            <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button"
                data-toggle="collapse" data-target="#<?php echo $cccClientNeedingSchoolSetUp?>"
                aria-expanded="false" aria-controls="collapseOne">
                Clients Needing School Set Up <span id="<?php echo $cccClientNeedingSchoolSetUp?>-counter"></span><i
                    class="fa fa-chevron-right"></i>
            </button>
        </h4>
    </div>

    <div id="<?php echo $cccClientNeedingSchoolSetUp?>" class="collapse"
        aria-labelledby="dailyactivityone" data-parent="#ccc_school_setup_childs">
        <div class="filters-common">
            @component('components.datatable-filters', ['filters' => $schoolSetupFilter, 'formname' =>
            'ccc_school_setup'])
            @endcomponent
        </div>
        <?php echo H::getHelper("SchoolSetup")->getDataTableHtml($cccClientNeedingSchoolSetUp,'#ccc_school_setup');
        ?>
    </div>
</div>


<script type="text/javascript">
    const clientNeedingSchoolSetUpBucket = ["{{$cccClientNeedingSchoolSetUp}}"];

    $(document).on('submit', '#ccc_school_setup', function (e) {
        e.preventDefault();
        var sData = $("#ccc_school_setup").serializeArray();
        for (var i = 0; i < clientNeedingSchoolSetUpBucket.length; i++) {
            var tempObject = clientNeedingSchoolSetUpBucket[i].replace(/ /g, "_");
            if ($("#reload-" + tempObject).val() == 1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{route('ccc.reloadClientSchoolSetup')}}?type=" + clientNeedingSchoolSetUpBucket[i],
                    type: "POST",
                    data: {
                        filters: sData,
                        _token: _token
                    },
                    success: function (response) {
                        var jsonObject = JSON.parse(response);
                        var type = jsonObject.type;
                        var idObject = type.replace(/ /g, "_");

                        $("#" + idObject + '-counter').html(jsonObject.counter);
                    },
                });
            } else {
                $("#dataTable-" + tempObject).DataTable().ajax.reload();
            }
        }

    });
    $("#ccc_school_setup").trigger("submit");

    $(document).on("change", '#ccc_school_setup_state', function () {
        var state = $(this).val().join(",");
        if (state.indexOf('TX') !== -1) {
            $('select[name="ccc_school_setup_region"]').multiselect('enable');
        } else {
            $('select[name="ccc_school_setup_region"]').multiselect('disable');
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
                $('#ccc_school_setup_aoc_facility_id').empty();
                $.each(response, function (index, value) {
                    $("#ccc_school_setup_aoc_facility_id").append(
                        '<option value="' + value.id + '">' + value.FacilityName + '</option>')
                });
                $("#ccc_school_setup_aoc_facility_id").multiselect("rebuild");
            },
        });
    });
</script>
