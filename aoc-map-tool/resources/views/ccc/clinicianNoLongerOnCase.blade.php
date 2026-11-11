<?php
use App\Helpers\H;
use App\Helpers\LD;

$cccrenewablefilter = [
    [
        'label' => 'Me Mode',
        'name' => 'no_longer_on_case_default',
        'type' => 'checkbox',
        'value' => true
    ],
    [
        'label' => 'State',
        'name' => 'no_longer_on_case_state',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],
    [
        'label' => 'Region',
        'name' => 'no_longer_on_case_region',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label' => 'Branch',
        'name' => 'no_longer_on_case_aoc_facility_id',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],
    [
        'label' => 'Clinical Care Cordinator',
        'name' => 'no_longer_on_case_clinical_care_manager',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
    ]
];
    $cccBucketKey = 'clinician_no_longer_on_case';
?>
<div class="card-body">

    <div class="card">
        <div class="card-header" id="dailyactivityone">
            <h4 class="mb-0">
                <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button"
                    data-toggle="collapse" data-target="#<?php echo $cccBucketKey?>" aria-expanded="false"
                    aria-controls="collapseOne">
                    Clinician's No Longer on Case <span id="<?php echo $cccBucketKey?>-counter"></span> <i
                        class="fa fa-chevron-right"></i>
                </button>
            </h4>
        </div>

        <div id="<?php echo $cccBucketKey?>" class="collapse " aria-labelledby="dailyactivityone" data-parent="#client_care_childs">
            <p style="text-align: left;">
                <u>
                    <b>Responsible Party:</b> Client Care Coordinator
                </u><br>
                <b>Description:</b> Provide outcome for each nurse that is no longer working their current case.<br>
                <b>Timeline:</b>
            </p>
            <div class="filters-common">
                @component('components.datatable-filters', ['filters' => $cccrenewablefilter, 'formname' =>
                'no_longer_on_case'])
                @endcomponent
            </div>
            <?php             
                echo H::getHelper("ClinicianNoLongerOnCase")->getDataTableHtml($cccBucketKey);
            ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    const noLongerOnCase = ["{{$cccBucketKey}}"];

    $(document).on('submit', '#no_longer_on_case', function (e) {
        e.preventDefault();
        var sData = $("#no_longer_on_case").serializeArray();
        for (var i = 0; i < noLongerOnCase.length; i++) {
            var tempObject = noLongerOnCase[i].replace(/ /g, "_");
            if ($("#reload-" + tempObject).val() == 1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{route('ccc.reloadClinicianNoLongerOnCase')}}?type=" + noLongerOnCase[i],
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
    $("#no_longer_on_case").trigger("submit");

    $(document).on("change", '#no_longer_on_case_state', function () {
        var state = $(this).val().join(",");
        if (state.indexOf('TX') !== -1) {
            $('select[name="no_longer_on_case_region"]').multiselect('enable');
        } else {
            $('select[name="no_longer_on_case_region"]').multiselect('disable');
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
                $('#no_longer_on_case_aoc_facility_id').empty();
                $.each(response, function (index, value) {
                    $("#no_longer_on_case_aoc_facility_id").append('<option value="' + value.id + '">' + value.FacilityName + '</option>')
                });
                $("#no_longer_on_case_aoc_facility_id").multiselect("rebuild");
            },
        });
    });
</script>
