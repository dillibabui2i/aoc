<?php
use App\Helpers\H;
use App\Helpers\LD;

$clientStatus  = '';
if(isset($patientsDetails->id)){
    $clientStatus = H::getHelper('ConstantHelper')->getClientStatusById($patientsDetails->id);
}
?>
<style>
    #supervisoryVisitFormModal label {
        line-height: 0px;
    }

    #supervisoryVisitFormModal .form-check-input-reverse {
        margin-left: 10px;
    }

    #supervisoryVisitFormModal input[type=checkbox] {
        height: 20px;
        width: 20px;
        vertical-align: middle;
    }

    #supervisoryVisitFormModal input[type=checkbox].error {
        outline: 1px dashed red;
    }
</style>
<x-modal>
    <x-slot name="id">supervisoryVisitFormModal</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">Supervisory Visit \ {{$clinicianDetails->fullName}}</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            <a data-bs-dismiss="modal" href="javascript:void(0);"
                onclick="event.preventDefault();openNotes('{{$clinicianDetails->id}}','clinician','Clinician Notes Added')">
                <i class="fa  fa-sticky-note-o"></i>
            </a>
        </div>
    </x-slot>
    <x-slot name="body">
        <form id="supervisoryVisitForm" class="supervisoryVisitForm">
            <input type="hidden" name="id" id="id" value="{{$id}}" />
            <input type="hidden" name="patient_id" id="patient_id" value="{{$patientsDetails->id}}" />
            <input type="hidden" name="provider_id" id="provider_id" value="{{$clinicianDetails->id}}" />
            <div class="row d-flex">
                <div class="col-md-12 tracker-details clinician-cnt">
                    <div class="col-md-12 card-cnt p-0 d-flex">
                        <div class="card mr-5">
                            <div class="card-body">
                                <h5 class="card-title bg-blue">Clinician</h5>
                                <div class="detail-section">
                                    <h6>
                                        {{$clinicianDetails->FirstName}} {{$clinicianDetails->LastName}}
                                    </h6>
                                    <p class="card-text">
                                        {{$clinicianDetails->ClinicianID}}
                                    </p>
                                    <p class="card-text">
                                        {{$clinicianDetails->State}}-{{$clinicianDetails->City}}
                                    </p>

                                    <p class="card-text">
                                        {{$clinicianDetails->Phone}}
                                    </p>
                                    <p class="card-text">
                                        {{$clinicianDetails->Mobile}}
                                    </p>
                                    <p class="card-text">
                                        {{$clinicianDetails->External_Email}}
                                    </p>
                                    <div class="tags-cnt">
                                        <span class='tag'>
                                            {{ $clinicianDetails->DisciplineName }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title bg-blue">Client</h5>
                                <div class="detail-section">
                                    <div class="row">
                                        <div class="col-md-6">{{$patientsDetails->Patient_FirstName}}
                                            {{$patientsDetails->Patient_LastName}}</div>
                                        <div class="col-md-6">DOB: {{H::getFormatedDate($patientsDetails->Patient_DOB)}}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="card-text">{{$patientsDetails->PatientAccount}}</p>
                                        </div>
                                        <div class="col-md-6">Age: {{$patientsDetails->Patient_age}}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="card-text">{{$patientsDetails->FacilityName}}</p>
                                        </div>
                                        <div class="col-md-6">Weight: {{$patientsDetails->CF_Weight}}</div>
                                    </div>
                                    <div class="row pt-3">
                                        <div class="col-md-6">
                                            <p class="card-text">{{$patientsDetails->Patient_Phone}}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="card-text">
                                                @if($patientsDetails->Patient_MobilPhone)
                                                {{$patientsDetails->Patient_MobilPhone}} (mobile)
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <div class="row pt-3">
                                        <div class="col-md-6">
                                            <p class="card-text">{{$patientsDetails->Patient_Address1}}
                                                {{$patientsDetails->Patient_Address2}}</p>
                                            <p class="card-text">{{$patientsDetails->Patient_City}}
                                                {{$patientsDetails->Patient_State}} {{$patientsDetails->Patient_Zip}}
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            @if($patientsDetails->Patient_Gender != '')
                                            <div class="tags-cnt">
                                                <span class='tag tag-grey'>{{$patientsDetails->Patient_Gender}}</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-9 p-0 mt-2 mb-2">
                @if (LD::checkFeatureFlag("add-sup-visit-date-and-visit-not-matched-column"))
                <div class="mt-2 mb-2">
                    <label for="form_sup_visit_date" class="mb-0"><b>Supervisory Visit Date</b></label>
                    <input type="text" class="form-control datepicker w-50"
                    name="form_sup_visit_date" id="form_sup_visit_date" {{($mode=='view' ) ? 'readonly' : '' }}
                    value="{{ !empty($supervisoryVisitData->form_sup_visit_date) ?
                    date('m/d/Y', strtotime($supervisoryVisitData->form_sup_visit_date)) : date('m/d/Y')}}">
                </div>
                @endif
                <div class="mt-2 mb-2">
                    <label for="item1_score" class="mb-0"><b>Job Knowledge</b></label>
                    <span class="form-text mb-1">Functions proficiently within the job description for his/her
                        position</span>
                    <select name="item1_score" id="item1_score" class="form-control w-50" aria-describedby=""
                        {{($mode=='view' ) ? 'disabled' : '' }}>
                        <option value=""> Please Select </option>
                        @foreach(config('constant')['supervisorVisitOutcome'] as $k => $v)
                            <option value="{{$k}}" {{($supervisoryVisitData->item1_score == $k) ? 'selected' : ''}}>
                                {{$v}}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-2 mb-2">
                    <label for="item2_score" class="mb-0"><b>Work Quality</b></label>
                    <span class="form-text mb-1">Follows Plan Of Care, Makes appropriate action always mindful of safety
                        standards/precautions</span>
                    <select name="item2_score" id="item2_score" class="form-control w-50" aria-describedby=""
                        {{($mode=='view' ) ? 'disabled' : '' }}>
                        <option value=""> Please Select </option>
                        @foreach(config('constant')['supervisorVisitOutcome'] as $k => $v)
                            <option value="{{$k}}" {{($supervisoryVisitData->item2_score == $k) ? 'selected' : ''}}>
                                {{$v}}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-2 mb-2">
                    <label for="item3_score" class="mb-0"><b>Attendance, Punctuality, and Dependability</b></label>
                    <span class="form-text mb-1">Reports for duty promptly for scheduled shifts, Timely submission of
                        notes.
                        Responds promptly to communication from office staff</span>
                    <select name="item3_score" id="item3_score" class="form-control w-50" aria-describedby=""
                        {{($mode=='view' ) ? 'disabled' : '' }}>
                        <option value=""> Please Select </option>
                        @foreach(config('constant')['supervisorVisitOutcome'] as $k => $v)
                            <option value="{{$k}}" {{($supervisoryVisitData->item3_score == $k) ? 'selected' : ''}}>
                                {{$v}}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-2 mb-2">
                    <label for="item4_score" class="mb-0"><b>Professionalism and Communication</b></label>
                    <span class="form-text mb-1">Handles situations in a professional manner with few to no complaints,
                        Maintains good communication with branch regarding shifts and client information</span>
                    <select name="item4_score" id="item4_score" class="form-control w-50" aria-describedby=""
                        {{($mode=='view' ) ? 'disabled' : '' }}>
                        <option value=""> Please Select </option>
                        @foreach(config('constant')['supervisorVisitOutcome'] as $k => $v)
                            <option value="{{$k}}" {{($supervisoryVisitData->item4_score == $k) ? 'selected' : ''}}>
                                {{$v}}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-3 mb-3 form-check pl-0">
                    <label for="item4_score" class="mb-0 form-check-label">Sup Visit has been completed and uploaded in
                        KanTime</label>
                    <input type="hidden" value="0" id="sv_uploaded_to_kantime" name="sv_uploaded_to_kantime">
                    <input class="form-check-input form-check-input-reverse" type="checkbox" value="1"
                        id="sv_uploaded_to_kantime" name="sv_uploaded_to_kantime"
                        {{($supervisoryVisitData->sv_uploaded_to_kantime == 1) ? 'checked' : ''}}
                    {{($mode=='view') ? 'disabled' : '' }}>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 text-center mt-2">
                    @if($mode != 'view')
                    <button class="btn btn-primary submit"
                        onclick="saveSupervisoryVisit(event, this);">Submit</button>
                    @else
                    <button class="btn btn-primary submit"
                        onclick="event.preventDefault();closeSupervisoryVisitPopupu()">Close</button>
                    @endif
                </div>
            </div>
        </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script src="{{ mix('js/confetti.js') }}"></script>
<script type="text/javascript">
    function showConfetti() {
        startConfetti();
        setTimeout(function() {
            stopConfetti();
        }, 1000);
    }
    var validator = $('#supervisoryVisitForm').validate(
        {
            rules:
            {
                'item1_score': { required: true },
                'item2_score': { required: true },
                'item3_score': { required: true },
                'item4_score': { required: true },
                'sv_uploaded_to_kantime': { required: true },
                @if (LD::checkFeatureFlag("add-sup-visit-date-and-visit-not-matched-column"))
                'form_sup_visit_date': { required: true },
                @endif
            },
            errorPlacement: function (error, element) {
            },
        }
    );

    function saveSupervisoryVisit(e, button) {
        e.preventDefault();
        var isValidated = true;

        if (!$('#supervisoryVisitForm').valid()) {
            isValidated = false;
        } else {
            validator.resetForm();
            isValidated = true;
        }

        if (isValidated) {
            button.innerHTML = 'Please wait...';
            button.disabled = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            let data = $('#supervisoryVisitForm').serializeArray();
            data.push({ name: "_token", value: _token });
            $.ajax({
                url: "{{route('supervisoryVisits.saveForm')}}",
                type: 'post',
                data: $.param(data),
                success: function (data) {
                    showConfetti();
                    button.disabled = false;
                    button.innerHTML = 'Submit';

                    if (data.status) {
                        closeSupervisoryVisitPopupu();
                        if ($('#supervisoryVisitDataTable').length) {
                            $('#supervisoryVisitDataTable').DataTable().ajax.reload();
                        }
                        if ($('#dataTable-super_visory_visits_bucket').length) {
                            $('#dataTable-super_visory_visits_bucket').DataTable().ajax.reload();
                        }
                    }
                }
            });
        }
    }

    function closeSupervisoryVisitPopupu() {
        $('#supervisoryVisitFormModal').modal('hide');
    }
    @if (LD::checkFeatureFlag("add-sup-visit-date-and-visit-not-matched-column"))
        @if($mode !='view')
        $('#form_sup_visit_date').datepicker({
            beforeShow: function (input, inst) {
                setTimeout(function () {
                    $('#ui-datepicker-div').appendTo($(input).parent());
                    inst.dpDiv.css({
                        top: 'initial',
                    });
                }, 0);
            },
            maxDate: new Date() // Set max date to today
        });
        @endif
    @endif
</script>
