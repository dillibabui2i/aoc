<?php
use App\Helpers\H;
use App\Helpers\LD;
$coordinators = H::getHelper('ConstantHelper')->getClientCareCoordinator();
?>
<x-modal>
    <x-slot name="id">schoolSetupsFormModal</x-slot>
    <x-slot name="size">w60</x-slot>
    @if($mode == 'add')
        <x-slot name="title">Add School Set Up</x-slot>
    @elseif($mode == 'edit')
        <x-slot name="title">Edit School Set Up</x-slot>
    @else
        <x-slot name="title">View School Set Up</x-slot>
    @endif
    <x-slot name="extraHeader">
    </x-slot>
    <x-slot name="body">
        <div class="modal-body" id="schoolSetupsSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" onclick="closeSchoolSetupPopup()" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <form id="schoolSetupsForm" class="schoolSetupsForm">
            <input
                type="hidden"
                name="aoc_patient_id"
                id="aoc_patient_id"
                value="{{ ($mode == 'add') ?
                (($ref == 'sidebar') ? '' :$patientsDetails->id) :
                $schoolSetupData->aoc_patient_id}}" />
            <input
                type="hidden"
                name="mode"
                id="mode"
                value="{{$mode}}" />
            <input
                type="hidden"
                name="ref"
                id="ref"
                value="{{$ref}}" />
            @if($mode == 'edit')
            <input
                type="hidden"
                name="id"
                id="id"
                value="{{$schoolSetupData->id}}" />
            @endif
            @csrf
            <div class="row form-group">
                <div class="col-md-12">
                    @if(LD::checkFeatureFlag('add-descriptions-to-add-school-set-up-form'))
                        <label for="assigned_ccc"><b>Coordinator</b></label>
                    @else
                        <label for="assigned_ccc">Coordinator</label>
                    @endif
                    <select
                        id="assigned_ccc"
                        name="assigned_ccc"
                        class="form-control"
                        autocomplete="off"
                        {{ ($mode == 'view') ? 'disabled' : '' }}>
                        <option value="">Please select</option>
                        @foreach($coordinators as $coordinator)
                            <option value="{{$coordinator['value']}}"
                            {{($mode == 'add' && $ref != 'sidebar' &&
                             $patientsDetails->ccc_id == $coordinator['value'])
                             ? 'selected' : ''}}
                            {{(
                                $mode != 'add' &&
                                $schoolSetupData->assigned_ccc == $coordinator['value']
                              ) ? 'selected' : ''}}
                            >{{$coordinator['text']}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row form-group">
                <div class="col-md-6">
                    @if(LD::checkFeatureFlag('add-descriptions-to-add-school-set-up-form'))
                        <label for="client_name"><b>Client Name</b></label>
                    @else
                        <label for="client_name">Client Name</label>
                    @endif
                    <input
                        type="input"
                        class="form-control"
                        id="client_name"
                        aria-describedby="Client Name"
                        name="client_name"
                        autocomplete="off"
                        {{ $ref != 'sidebar' ? 'disabled': ''}}
                        value="{{($ref == 'sidebar') ?
                         '' :$patientsDetails->Patient_FirstName . ' ' .
                          $patientsDetails->Patient_LastName}}">
                </div>
                <div class="col-md-6">
                    @if(LD::checkFeatureFlag('add-descriptions-to-add-school-set-up-form'))
                        <label for="start_date"><b>Date Client and Nurse are to Start School</b></label>
                    @else
                        <label for="start_date">Date Client and Nurse are to Start School</label>
                    @endif
                    @if(LD::checkFeatureFlag('add-save-button-in-ssu-form-from-bucket'))
                    <input
                        type="input"
                        class="form-control"
                        id="start_date"
                        aria-describedby="Date Client and Nurse are to Start School"
                        name="start_date"
                        autocomplete="off"
                        value="{{ ($mode == 'add') ? (($schoolSetupData->start_date ?? '') ? H::getFormatedDate($schoolSetupData->start_date) : '') : H::getFormatedDate($schoolSetupData->start_date) }}"
                        {{ ($mode == 'view') ? 'disabled' : 'readonly' }}>
                    @else
                    <input
                        type="input"
                        class="form-control"
                        id="start_date"
                        aria-describedby="Date Client and Nurse are to Start School"
                        name="start_date"
                        autocomplete="off"
                        value = "{{ ($mode == 'add') ? '' : H::getFormatedDate($schoolSetupData->start_date)}}"
                        {{ ($mode == 'view') ? 'disabled' : 'readonly' }}>
                    @endif
                </div>
            </div>
            <div class="clinicianDiv">
            </div>
            @if ($mode == 'add')
            <div class="row form-group">
                <div class="col-md-6">
                    <a href="javascript:void(0);" onclick="addMoreClinician()" id="add_more">Add More</a>
                </div>
            </div>
            @endif
            <div class="row form-group">
                <div class="col-md-6">
                    @if(LD::checkFeatureFlag('add-descriptions-to-add-school-set-up-form'))
                        <label for="school_name"><b>School Name</b></label>
                        <span style="line-height: 20px;">Name of building the client and clinician will enter. Example: Sory Elementary</span>
                    @else
                        <label for="school_name">School Name</label>
                    @endif
                    @if(LD::checkFeatureFlag('add-save-button-in-ssu-form-from-bucket'))
                        @if($mode == 'add' && !empty($schoolSetupData))
                        <input
                            type="input"
                            class="form-control"
                            id="school_name"
                            aria-describedby="School Name"
                            name="school_name"
                            autocomplete="off"
                            value="{{ $schoolSetupData->school_name ?? '' }}"
                            {{ ($mode == 'view') ? 'disabled' : '' }}>
                        @else
                        <input
                            type="input"
                            class="form-control"
                            id="school_name"
                            aria-describedby="School Name"
                            name="school_name"
                            autocomplete="off"
                            value="{{
                                ($mode == 'add') ? (($ref == 'sidebar') ?
                                '' :$patientsDetails->CF_SchoolName)
                                : $schoolSetupData->school_name
                            }}"
                            {{ ($mode == 'view') ? 'disabled' : '' }}>
                        @endif
                    @else
                    <input
                        type="input"
                        class="form-control"
                        id="school_name"
                        aria-describedby="School Name"
                        name="school_name"
                        autocomplete="off"
                        value="{{
                            ($mode == 'add') ? (($ref == 'sidebar') ?
                             '' :$patientsDetails->CF_SchoolName)
                              : $schoolSetupData->school_name
                        }}"
                        {{ ($mode == 'view') ? 'disabled' : '' }}>
                    @endif
                </div>
                <div class="col-md-6">
                    @if(LD::checkFeatureFlag('add-descriptions-to-add-school-set-up-form'))
                        <label for="school_district"><b>School District</b></label>
                        <span style="line-height: 20px;">Full name of the school district the client attends. Example: Sherman Independent School District instead of SISD.</span>
                    @else
                        <label for="school_district">School District</label>
                    @endif
                    @if(LD::checkFeatureFlag('add-save-button-in-ssu-form-from-bucket'))
                        @if($mode == 'add' && !empty($schoolSetupData))
                        <input
                            type="input"
                            class="form-control"
                            id="school_district"
                            aria-describedby="School District"
                            name="school_district"
                            autocomplete="off"
                            value="{{
                                $schoolSetupData->school_district ?? ''
                            }}"
                            {{ ($mode == 'view') ? 'disabled' : '' }}>
                        @else
                            <input
                            type="input"
                            class="form-control"
                            id="school_district"
                            aria-describedby="School District"
                            name="school_district"
                            autocomplete="off"
                            value="{{
                                ($mode == 'add') ? (($ref == 'sidebar') ?
                                '' :$patientsDetails->CF_SchoolDistrict)
                                : $schoolSetupData->school_district
                            }}"
                            {{ ($mode == 'view') ? 'disabled' : '' }}>
                        @endif
                    @else
                    <input
                        type="input"
                        class="form-control"
                        id="school_district"
                        aria-describedby="School District"
                        name="school_district"
                        autocomplete="off"
                        value="{{
                            ($mode == 'add') ? (($ref == 'sidebar') ?
                             '' :$patientsDetails->CF_SchoolDistrict)
                              : $schoolSetupData->school_district
                        }}"
                        {{ ($mode == 'view') ? 'disabled' : '' }}>
                    @endif
                </div>
            </div>
            <div class="row form-group">
                <div class="col-md-6">
                    @if(LD::checkFeatureFlag('add-descriptions-to-add-school-set-up-form'))
                        <label for="school_contact"><b>Name of Contact at School</b></label>
                        <span style="line-height: 20px;">Full name of the person that can assist with clinician clearance at the school</span>
                    @else
                        <label for="school_contact">Name of Contact at School</label>
                    @endif
                    @if(LD::checkFeatureFlag('add-save-button-in-ssu-form-from-bucket'))
                        @if($mode == 'add' && !empty($schoolSetupData))
                            <input
                            type="input"
                            class="form-control"
                            id="school_contact"
                            aria-describedby="Name of Contact at School"
                            name="school_contact"
                            autocomplete="off"
                            value = "{{ $schoolSetupData->school_contact ?? ''}}"
                            {{ ($mode == 'view') ? 'disabled' : '' }}>
                        @else
                            <input
                            type="input"
                            class="form-control"
                            id="school_contact"
                            aria-describedby="Name of Contact at School"
                            name="school_contact"
                            autocomplete="off"
                            value = "{{ ($mode == 'add') ? '' : $schoolSetupData->school_contact}}"
                            {{ ($mode == 'view') ? 'disabled' : '' }}>
                        @endif
                    @else
                    <input
                        type="input"
                        class="form-control"
                        id="school_contact"
                        aria-describedby="Name of Contact at School"
                        name="school_contact"
                        autocomplete="off"
                        value = "{{ ($mode == 'add') ? '' : $schoolSetupData->school_contact}}"
                        {{ ($mode == 'view') ? 'disabled' : '' }}>
                    @endif
                </div>
                <div class="col-md-6">
                    @if(LD::checkFeatureFlag('add-descriptions-to-add-school-set-up-form'))
                        <label for="school_contact_info"><b>Contact email or phone number</b></label>
                        <span style="line-height: 20px;">Correct email or phone number of the person that can assist with clinician clearance at the school</span>
                    @else
                        <label for="school_contact_info">Contact email or phone number</label>
                    @endif
                    @if(LD::checkFeatureFlag('add-save-button-in-ssu-form-from-bucket'))
                        @if($mode == 'add' && !empty($schoolSetupData))
                        <input
                            type="input"
                            class="form-control"
                            id="school_contact_info"
                            aria-describedby="School Discrict"
                            name="school_contact_info"
                            autocomplete="off"
                            value = "{{ $schoolSetupData->school_contact_info ?? ''}}"
                            {{ ($mode == 'view') ? 'disabled' : '' }}>
                        @else
                            <input
                            type="input"
                            class="form-control"
                            id="school_contact_info"
                            aria-describedby="School Discrict"
                            name="school_contact_info"
                            autocomplete="off"
                            value = "{{ ($mode == 'add') ? '' : $schoolSetupData->school_contact_info}}"
                            {{ ($mode == 'view') ? 'disabled' : '' }}>
                        @endif
                    @else
                    <input
                        type="input"
                        class="form-control"
                        id="school_contact_info"
                        aria-describedby="School Discrict"
                        name="school_contact_info"
                        autocomplete="off"
                        value = "{{ ($mode == 'add') ? '' : $schoolSetupData->school_contact_info}}"
                        {{ ($mode == 'view') ? 'disabled' : '' }}>
                    @endif
                </div>
            </div>
            <div class="row form-group">
                <div class="col-md-12">
                    @if(LD::checkFeatureFlag('add-descriptions-to-add-school-set-up-form'))
                        <label for="notes"><b>Note</b></label>
                    @else
                        <label for="notes">Note</label>
                    @endif
                    @if(LD::checkFeatureFlag('add-save-button-in-ssu-form-from-bucket'))
                        @if($mode == 'add' && !empty($schoolSetupData))
                        <textarea
                            class="form-control"
                            id="notes"
                            aria-describedby="notes"
                            name="notes"
                            autocomplete="off"
                            {{ ($mode == 'view') ? 'disabled' : '' }}
                        >{{ $schoolSetupData->notes ?? ''}}</textarea>
                        @else
                        <textarea
                            class="form-control"
                            id="notes"
                            aria-describedby="notes"
                            name="notes"
                            autocomplete="off"
                            {{ ($mode == 'view') ? 'disabled' : '' }}
                        >{{ ($mode == 'add') ? '' : $schoolSetupData->notes}}</textarea>
                        @endif
                    @else
                    <textarea
                        class="form-control"
                        id="notes"
                        aria-describedby="notes"
                        name="notes"
                        autocomplete="off"
                        {{ ($mode == 'view') ? 'disabled' : '' }}
                    >{{ ($mode == 'add') ? '' : $schoolSetupData->notes}}</textarea>
                    @endif
                </div>
            </div>
            @if($mode != 'view')
                <div class="row form-group">
                    <div class="col-md-12 text-center mt-2">
                        <button class="btn btn-primary submit"
                            onclick="saveSchoolSetupForm(event, this, 'submit');">Submit</button>
                        @if(LD::checkFeatureFlag('add-save-button-in-ssu-form-from-bucket'))
                            @if($mode == 'add' && $ref != 'sidebar')
                                <button class="btn btn-primary save"
                                    onclick="saveSchoolSetupForm(event, this, 'save');">Save</button>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="row form-group" id="actionMessage">
            @endif
            </div>
        </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script type="text/javascript">
    var validator = $('#schoolSetupsForm').validate(
        {
            rules:
            {
                'assigned_ccc': { required: true },
                'start_date': { required: true },
                'school_contact_info': { required: true },
                'school_contact': { required: true },
                @if($ref == 'sidebar')
                'client_name': { required: true },
                @endif
                @if(LD::checkFeatureFlag('make-school-setup-form-field-required'))
                    'school_name' : { required: true },
                    'school_district' : { required: true },
                @endif
            },
            errorPlacement: function (error, element) {
            },
        }
    );

    var count = 0;
    function addMoreClinician(name = '', clinicianID = '', aoc_provider_id = '') {
        let isBold = "";

        @if(LD::checkFeatureFlag('add-descriptions-to-add-school-set-up-form'))
            isBold = "font-weight: bold;";
        @endif
        $('.clinicianDiv').append('<div class="row form-group" id="clinician_div_' + count + '">\
                <input\
                    type="hidden"\
                    name="aoc_provider_id[]" value = "' + (aoc_provider_id != '' ? aoc_provider_id : '') + '"\
                    id="aoc_provider_id_' + count + '"/>\
                <div class="col-md-6">\
                    <label for="clinican_' + count + '" style="' + isBold + '">Clinician</label>\
                    <input\
                        type="input"\
                        class="form-control"\
                        id="clinican_' + count + '"\
                        aria-describedby="Clinican"\
                        name="clinican[]"\
                        autocomplete="off"\
                        value = "' + (name != '' ? name : '') + '" {{ ($mode == 'view') ? 'disabled' : '' }}\
                        data-count="' + count + '">\
                </div>\
                <div class="col-md-6">\
                    <label for="clinican_id_' + count + '" style="' + isBold + '">Clinician Id</label>\
                    <input\
                        type="input"\
                        class="form-control '+ (count > 0 ? 'sortinput' : '') +'"\
                        id="clinican_id_' + count + '"\
                        aria-describedby="Clinican Id"\
                        name="clinican_id[]"\
                        autocomplete="off" value = "' + (clinicianID != '' ? clinicianID : '') + '" disabled>\
                    '+ ((count > 0) ? '\
                    <a href="javascript:void(0);" class="remove-clinician" data-count="' + count + '">\
                        <i class="fa fa-times-circle-o"></i>\
                    </a>' : '') +'\
                </div>\
            </div>');

        $("#clinican_" + count).rules("add", "required");
        window["flag_" + count] = false;
        $("#clinican_" + count).autocomplete({
            source: function(request, response) {
                $.getJSON("{{ route('getCandidateName') }}", {
                    status: 'current',
                    term: request.term,
                    schoolsetup: true
                }, response);
            },
            minLength: 2,
            select: function(event, ui) {
                let currentCount = $(event.target).data('count')
                this["flag_" + currentCount] = true;
                $('#clinican_id_' + currentCount).val(ui.item.ClinicianID);
                $('#aoc_provider_id_' + currentCount).val(ui.item.id);
            }
        });
        $('#clinican_' + count).keyup(function() {
            if ($(this).val() == '') {
                let currentCount = $(this).data('count')
                $('#clinican_id_' + currentCount).val('');
                $('#aoc_provider_id_' + currentCount).val('');
                this["flag_" + currentCount] = false;
            }
        });
        $('#clinican_' + count).blur(function() {
            let currentCount = $(this).data('count')
            if (this["flag_" + currentCount] == false) {
                $(this).val('');
                $('#clinican_id_' + currentCount).val('');
                $('#aoc_provider_id_' + currentCount).val('');
            }
            this["flag_" + currentCount] = false;
        });

        $('.remove-clinician').off().click(function() {
            $('#clinician_div_' + $(this).data('count')).remove();
        });

        count++;
    }

    @if($mode == 'view' || $mode == 'edit')
        addMoreClinician(
            '{{$providerData?->FirstName . " " . $providerData?->LastName}}',
            '{{$providerData?->ClinicianID}}', '{{$providerData?->id}}'
        );
    @else
        @if (count($clinicianDetails) == 0)
            addMoreClinician('', '', '');
        @else
            @foreach($clinicianDetails as $provider)
                addMoreClinician(
                    '{{$provider?->FirstName . " " . $provider?->LastName}}',
                    '{{$provider?->ClinicianID}}',
                    '{{$provider?->id}}'
                );
            @endforeach
        @endif
    @endif

    $('#start_date').datepicker({
        beforeShow: function(input, inst) {
            var calendar = inst.dpDiv;
            setTimeout(function() {
                calendar.position({
                    my: 'left top',
                    at: 'left bottom',
                    collision: 'none',
                    of: input
                });
            }, 1);
        }
    });

    function saveSchoolSetupForm(e, button, type = null) {
        e.preventDefault();
        var isValidated = true;
        var buttonType = type;
        $('#actionMessage').html('');
        @if(LD::checkFeatureFlag('add-save-button-in-ssu-form-from-bucket'))
            if(buttonType != 'save'){
                if (!$('#schoolSetupsForm').valid()) {
                    isValidated = false;
                } else {
                    validator.resetForm();
                    isValidated = true;
                }
            }
        @else
            if (!$('#schoolSetupsForm').valid()) {
                isValidated = false;
            } else {
                validator.resetForm();
                isValidated = true;
            }
        @endif
        if (isValidated) {
            $('#client_name').prop('disabled', true);
            button.innerHTML = 'Please wait...';
            button.disabled = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            let data = $('#schoolSetupsForm').serializeArray();
            data.push({ name: "_token", value: _token });
            @if(LD::checkFeatureFlag('add-save-button-in-ssu-form-from-bucket'))
                data.push({ name: "buttonType", value: buttonType})
            @endif
            $.ajax({
                url: "{{route('schoolSetup.saveForm')}}",
                type: 'post',
                data: $.param(data),
                success: function (response) {
                    console.log(response.msg);
                    button.disabled = false;
                    @if($ref == 'sidebar')
                    console.log("sidebar");
                        $("#successBody").html(response.msg);
                        $("#schoolSetupsForm").hide();
                        $("#schoolSetupsSuccessDiv").show();
                    @else
                    console.log("Submit");
                    $('#actionMessage').html(response.msg);
                    if (response.status) {
                        button.innerHTML = 'Submit';
                        closeSchoolSetupPopup();
                    }
                    @endif
                }
            });
        }
    }

    function closeSchoolSetupPopup() {
        $('#schoolSetupsFormModal').modal('hide');
    }

    $('#schoolSetupsFormModal').on('hidden.bs.modal', function (e) {
        @if($ref != 'sidebar')
        if ($('#dataTable-ccc_client_need_school_setup').length
            && $('#dataTable-ccc_client_need_school_setup').DataTable().data().length) {
            $('#dataTable-ccc_client_need_school_setup').DataTable().ajax.reload();
        }
        @endif
    });

    var clientNameFlag = false;
    $("#schoolSetupsForm #client_name").autocomplete({
        source: "{{ route('getScheduleClient') }}",
        classes: {
            "ui-autocomplete": "clientName"
        },
        minLength: 2,
        select: function(event, ui) {
            clientNameFlag = true;
            $('#schoolSetupsForm #aoc_patient_id').val(ui.item.id);
            $('#schoolSetupsForm #school_name').val(ui.item.SchoolName);
            $('#schoolSetupsForm #school_district').val(ui.item.SchoolDistrict);
            getClinicianByPatientId(ui.item.id);
        }
    });
    $('#schoolSetupsForm #client_name').keyup(function() {
        clientNameFlag = false;
        if ($(this).val() == '') {
            $('#schoolSetupsForm #client_name').val('');
            $('#schoolSetupsForm #aoc_patient_id').val(0);
        }
    });
    $('#schoolSetupsForm #client_name').blur(function() {
        if (clientNameFlag == false) {
            $(this).val('');
            $('#schoolSetupsForm #aoc_patient_id').val(0);
        }
    });

    function getClinicianByPatientId(aoc_patient_id) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('getClinicianByPatientId')}}",
            type: 'POST',
            data: {
                aoc_patient_id: aoc_patient_id,
                _token: _token
            },
            success: function (data) {
                console.log(data.length);
                count = 0;
                $('.clinicianDiv').html('');
                if(data.length > 0) {
                    for (const key in data) {
                        addMoreClinician(
                            data[key].FirstName,
                            data[key].ClinicianID,
                            data[key].id
                        );
                    }
                } else {
                    addMoreClinician('', '', '');
                }
            }
        });
    }
</script>
