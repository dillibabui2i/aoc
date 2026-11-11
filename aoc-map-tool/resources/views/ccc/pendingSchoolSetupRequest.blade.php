<?php
use App\Helpers\H;
use App\Helpers\LD;


$pendingSchoolSetupRequestFilter = [
    [
        'label' => 'Me Mode',
        'name' => 'ccc_pending_school_setup_request_default',
        'type' => 'checkbox',
        'value' => true
    ],
    [
        'label' => 'State',
        'name' => 'ccc_pending_school_setup_request_state',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],
    [
        'label' => 'Region',
        'name' => 'ccc_pending_school_setup_request_region',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label' => 'Branch',
        'name' => 'ccc_pending_school_setup_request_aoc_facility_id',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],
    [
        'label' => 'Client Care Coordinator',
        'name' => 'ccc_pending_school_setup_request_client_care_coordinator',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
    ],
    [
        'label'=>'Payer',
        'name'=>'ccc_pending_school_setup_request_payer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayers()
    ],[
        'label' => 'Special Payer Group',
        'name' => 'ccc_pending_school_setup_request_payer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ],
];
    $cccPendingSchoolSetUpRequest = 'ccc_pending_school_setups_request';
?>
<div class="card">
    <div class="card-header" id="dailyactivityone">
        <h4 class="mb-0">
            <button class="col-md-9 pull-left btn btn-link btn-block text-left collapsed" type="button"
                data-toggle="collapse" data-target="#<?php echo $cccPendingSchoolSetUpRequest?>"
                aria-expanded="false" aria-controls="collapseOne">
                Active School Set Up Requests
                <span id="<?php echo $cccPendingSchoolSetUpRequest?>-counter"></span><i
                    class="fa fa-chevron-right"></i>
            </button>
            <div class="col-md-3 text-right pull-left p-1">
        <a href="{{route('schoolsetups.showall')}}" class="dashboard-showall" title="Show All School Set Up Requests">
            Show All School Set Up Requests</a></div>
        </h4>
    </div>

    <div id="<?php echo $cccPendingSchoolSetUpRequest?>" class="collapse"
        aria-labelledby="dailyactivityone" data-parent="#ccc_school_setup_childs">
        <p style="text-align: left;">
            <u>
                <b>Responsible Party:</b> Client Care Coordinator
            </u><br>
            <b>Description:</b> Clients pending school set up request being processed.<br>
            <b>Timeline:</b> N/A
        </p>
        <div class="filters-common">
            @component('components.datatable-filters', ['filters' => $pendingSchoolSetupRequestFilter, 'formname' =>
            'ccc_pending_school_setup_request'])
            @endcomponent
        </div>
        <?php echo H::getHelper("SchoolSetup")->getDataTableHtml($cccPendingSchoolSetUpRequest,
        '#ccc_pending_school_setup_request');
        ?>
    </div>
</div>
<x-modal>
        <x-slot name="id">SsuRequest</x-slot>
        <x-slot name="size">w-50</x-slot>
        <x-slot name="title"></x-slot>
        <x-slot name="class"></x-slot>
        <x-slot name="body">
            <h5>Confirm this school set up needs to be removed?</h5>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-primary" id="SsuRequestConfirmationBtn" data-id="">Yes</button>
            <button type="button" class="btn btn-primary" data-dismiss="modal">No</button>
        </x-slot>
</x-modal>
<script type="text/javascript">
    const pendingSchoolSetUpRequestBucket = ["{{$cccPendingSchoolSetUpRequest}}"];

    $(document).on('submit', '#ccc_pending_school_setup_request', function (e) {
        e.preventDefault();
        var sData = $("#ccc_pending_school_setup_request").serializeArray();
        for (var i = 0; i < pendingSchoolSetUpRequestBucket.length; i++) {
            var tempObject = pendingSchoolSetUpRequestBucket[i].replace(/ /g, "_");
            if ($("#reload-" + tempObject).val() == 1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{route('ccc.reloadClientSchoolSetup')}}?type=" + pendingSchoolSetUpRequestBucket[i],
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
    $("#ccc_pending_school_setup_request").trigger("submit");

    $(document).on("change", '#ccc_pending_school_setup_request_state', function () {
        var state = $(this).val().join(",");
        if (state.indexOf('TX') !== -1) {
            $('select[name="ccc_pending_school_setup_request_region"]').multiselect('enable');
        } else {
            $('select[name="ccc_pending_school_setup_request_region"]').multiselect('disable');
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
                $('#ccc_pending_school_setup_request_aoc_facility_id').empty();
                $.each(response, function (index, value) {
                    $("#ccc_pending_school_setup_request_aoc_facility_id").append(
                        '<option value="' + value.id + '">' + value.FacilityName + '</option>')
                });
                $("#ccc_pending_school_setup_request_aoc_facility_id").multiselect("rebuild");
            },
        });
    });
@if(LD::checkFeatureFlag('new-ssu-form-change'))
function markSsuRequest(id) {
    $('#SsuRequest').modal('show');
    $('#SsuRequestConfirmationBtn').attr('data-id',id);
}
$(document).on('click', '#SsuRequestConfirmationBtn', function(e) {
    var id = $(this).attr('data-id');
    if(id != ''){
        SsuRequests(id);
    }

});
function SsuRequests(id){
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('markSsuRequest')}}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token,
            id: id
        },
        success: function(data) {
            $('#SsuRequest').modal('hide');
            if(data.status){
                $('.dataTable').DataTable().ajax.reload(null, false);
            }
        }
    });
}
@endif
</script>
