<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $dataTableFields = [
        ['name' => 'document_type','label' => 'Type'],
        ['name' => 'description', 'label' => 'Description'],
        ['name' => 'created_at', 'label' => 'Created On'],
        ['name' => 'signed_date', 'label' => 'Signed Date'],
        ['name' => 'document_filename', 'label' => 'Actions']
    ];
?>
<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<x-modal>
    <x-slot name="id">clinicianDetails</x-slot>
    <x-slot name="size">w90</x-slot>
    <x-slot name="title">Clinician Profile \ {{$clinicianDetails->fullName}}</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            <a href="javascript:void(0);" data-id="{{$clinicianDetails->id}}" id="addScheduleFormFromClinician"><i
                    class="fa fa-plus"></i></a>
            <a href="#"><i class="fa fa-map-marker"></i></a>
            <a data-bs-dismiss="modal" href="javascript:void(0);"
                onclick="event.preventDefault();openNotes('{{$clinicianDetails->id}}','clinician','Clinician Notes Added')"><i
                class="fa  fa-sticky-note-o"></i></a>
            <?php if(LD::checkFeatureFlag('conversation-icon-on-clinician-profile')){ ?>
                <a href="javascript:void(0);" data-candidate-name="{{$clinicianDetails->fullName}}" data-candidate-id="{{$clinicianDetails->id}}" data-candidate-phone="{{$clinicianDetails->Mobile}}"  id="startConversationWithClinicianBtn" >
                    <i class="fa fa-comment-o"></i>
                </a>
            <?php } ?>
        </div>
    </x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-9 tracker-details clinician-cnt">
                <div class="col-md-12 card-cnt p-0 d-flex">
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Clinician</h5>
                            <div class="detail-section">
                                <h6>
                                    {{$clinicianDetails->FirstName}} {{$clinicianDetails->LastName}}
                                </h6>
                                <div class="tags-cnt">
                                    <span class='tag'>
                                        {{ $clinicianDetails->DisciplineName }}
                                    </span>
                                </div>
                                <p class="card-text">
                                    {{$clinicianDetails->Phone}}
                                </p>
                                <p class="card-text">
                                    {{$clinicianDetails->Mobile}}
                                    <?php if(LD::checkFeatureFlag('display-twilio-error-on-profile-page')){ ?>
                                        <?php if(!empty($twilioErrorData)) { ?>
                                        &nbsp;<span data-toggle="tooltip" style="vertical-align:text-bottom"
                                        title="(Error:{{ $twilioErrorData->error_code }}) {{ $twilioErrorData->error_message }}">
                                            <i class="fa fa-times" style="font-size:16px;color:red"></i></span>
                                        <?php } ?>
                                    <?php } ?>
                                </p>
                                <p class="card-text">
                                    {{$clinicianDetails->External_Email}}
                                </p>
                                <ul class="d-flex att-cnt">
                                    <li>
                                        {{$clinicianDetails->Address1}}
                                    </li>
                                    <li>
                                        {{$clinicianDetails->Address2}}
                                    </li>
                                    <li>
                                        {{$clinicianDetails->City}}
                                    </li>
                                    <li>
                                       {{$clinicianDetails->State}}
                                    </li>
                                    <li>
                                        {{$clinicianDetails->Zip}}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title bg-blue"></h5>
                            <div class="detail-section">
                                <?php if(LD::checkFeatureFlag('clinician-profile-nth-alerts-status')) { ?>
                                    <h6>
                                    &nbsp;
                                    </h6>
                                    <div class="tags-cnt">
                                    &nbsp;
                                    </div>
                                    <p class="card-text">
                                    &nbsp;
                                    </p>
                                    <p class="card-text">
                                        &nbsp;
                                    </p>
                                    <p class="card-text">
                                        &nbsp;
                                    </p>
                                    <div class="">
                                            <?php if($clinicianDetails->SMS_Status) { ?>
                                            NTH Alerts : <span style="verticle-align:top">
                                                <?php if($clinicianDetails->SMS_Status=='Active') { ?>
                                                    <i style="font-size:16px;color:green;margin: 0px;vertical-align: middle;padding-bottom: 2px;" class="fa fa-check-square" aria-hidden="true"></i>
                                                <?php } else {?>
                                                    <i style="font-size:16px;color:red;margin: 0px;vertical-align: middle;padding-bottom: 2px;" class="fa fa-times" aria-hidden="true"></i>
                                                    <?php } ?>
                                            {{$clinicianDetails->SMS_Status}}
                                            <?php } ?>
                                        </span>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 p-0 tabs-container">
                    @include('clinician._tabs')
                </div>
            </div>
            <div class="col-md-3 d-flex p-0">
                <x-activity-log>
                    <x-slot name="trackerId"></x-slot>
                </x-activity-log>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script>
function reloadClinicianHistory(id, type, destination) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('clinicianhistory')}}",
        type: 'get',
        data: {
            id: id,
            type: type,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $(destination).html(data);
        }
    });
}

$(document).ready(function() {
    reloadClinicianHistory('<?php echo $clinicianDetails->id?>', 'clinicianhistory', '.activity-log');

    var fields = '<?php echo json_encode($dataTableFields);?>';
    fields = JSON.parse(fields);
    var columns = [];
    $.each(fields, function(key, val) {
        columns.push({
            data: val.name,
            name: val.name,
            visible: typeof val.visible !== 'undefined' ? val.visible : true,
            width: typeof val.width !== 'undefined' ? val.width : ''
        });
    });
    var payRateAgreementsTable;
        var dataTableDelay = 1000;
        payRateAgreementsTable = $('#dataTable-pay-rate-agreements-grid').DataTable({
            "order": [
                [2, "asc"]
            ],
            "ajax": {
                "url": "{{ route('rightSignature.payRateAgreementsList') }}?clinicianId={{$clinicianDetails->id}}",
                data: function(d) {
                    d.filters = $('#datatableFilterSearchForm').serializeArray();
                }
            },

            "processing": true,
            "serverSide": true,
            "initComplete": function(settings, json) {
                jQuery('#dataTable_filter input').off('keyup.DT input.DT');
                var searchDelay = null;
                jQuery('#dataTable_filter input').on('keyup', function(e) {
                    var search = jQuery('#dataTable_filter input').val();
                    clearTimeout(searchDelay);
                    searchDelay = setTimeout(function() {
                        if (search != null && search.length >= 3)
                            table.search(search).draw();
                        if (search.length == 0 && e.keyCode == 8)
                            table.search(search).draw();
                    }, dataTableDelay);
                });
            },
            "columns": columns
        });

    $('#refreshRenewableByProviderId').click(function () {
        let _token = $('meta[name="csrf-token"]').attr('content'); //todo improve me
        $.ajax({
            url: "{{ route('renewables.refresh.by.providerId') }}",
            type: 'get',
            data: {
                id: $(this).data('id'),
                _token: _token
            },
            dataType: 'json',
            success: function(data) {
                $('#refreshRenewableByProviderIdMessage').text(data.message).show();
            }
        });
    });

});
</script>
