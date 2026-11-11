<?php
    use App\Helpers\H;
?>
<x-modal>
    <x-slot name="id">nthsocialmedia_form</x-slot>
    <x-slot name="title">NTH Checklist</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="modal-body" id="nthSocialMediaSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" onclick="closeForm()" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div class="modal-body" id="nthSocialMediaFormDiv">
            <?php if($isClientRequestAvail) {?>
                <div class="col-md-12">
                    <div class="alert alert-danger" role="alert">An ad has already been submitted for this client.</div>
                </div>
            <?php } ?>
            <form method="post" autocomplete="off" id="nth_socialmedia_form" class="scaffold-form" enctype="multipart/form-data">
                <input type="hidden" name="aoc_nthaccounts_id" value="{{$id}}" />
                <input type="hidden" name="nthchecklist_id" value="{{$clientDetail->nthchecklist_id}}" />
                <input type="hidden" name="city" value="{{$clientDetail->Patient_City}}" />
                <input type="hidden" name="state" value="{{$clientDetail->Patient_State}}" />
                @csrf

                <div class="col-md-12 tracker-details mb-3">
                    <div class="col-md-12 card-cnt p-0 d-flex">
                        <div class="card  mr-4">
                            <div class="card-body">
                                <div class="detail-section">
                                    <p class="card-text font-weight-bold mb-1">
                                        {{$clientDetail->ClientNameCustom}}
                                    </p>
                                    <p class="card-text mb-1">
                                        <span class="font-weight-light">{{$clientDetail->PatientAccount}}</span>
                                    </p>
                                    <p class="card-text mb-1">
                                        <span class="mr-1 font-weight-normal">Payer: </span><span class="font-weight-light">{{$clientDetail->Payer}}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="detail-section">
                                    <p class="card-text mb-1">
                                        <span class="mr-1 font-weight-normal">NTH Status: </span><span class="font-weight-light">{{$clientDetail->Status}}</span>
                                    </p>
                                    <p class="card-text mb-1">
                                        <span class="mr-1 font-weight-normal">Date added to NTH: </span><span class="font-weight-light">{{isset($clientDetail->created_at) ? H::getFormatedDate($clientDetail->created_at) : ''}}</span>
                                        <span class="mr-1 font-weight-normal ml-4">Days On NTH: </span><span class="font-weight-light"><?php if(isset($clientDetail->created_at)) { $earlier = new DateTime($clientDetail->created_at); $later = new DateTime(now()); echo $later->diff($earlier)->format("%a");} ?></span>
                                    </p>
                                    <p class="card-text mb-1">
                                        <span class="mr-1 font-weight-normal">Services Needed: </span><span class="font-weight-light">{{$clientDetail->ServicesNeeded}}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 tracker-details mb-3">
                    <div class="col-md-12 card-cnt p-0 d-flex">
                        <div class="card  mr-4">
                            <div class="card-header pl-2">The Sale</div>
                            <div class="card-body">
                                <div class="detail-section">
                                    <p class="card-text text-justify">
                                        {{$clientDetail->CF_TheSale}}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header pl-2">NTH Notes</div>
                            <div class="card-body">
                                <div class="detail-section">
                                    <p class="card-text text-justify">
                                        {{$clientDetail->NTHNotes}}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if(!$isClientRequestAvail) {?>
                <div class="row">
                    <div class="col-md-12">
                        <label for="">Do you have media consent form and photo on file?</label>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="customRadio1" name="media_consent_form" class="custom-control-input media_consent_form" value="no">
                            <label class="custom-control-label" for="customRadio1">No</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="customRadio2" name="media_consent_form" class="custom-control-input media_consent_form" value="yes">
                            <label class="custom-control-label" for="customRadio2">Yes</label>
                        </div>
                    </div>
                </div>
                <?php }?>
                <!-- <div id="media_consent_form_no" class="media_consent_form_div">
                    <div class="row">
                        <div class="col-md-12">
                            <label for="the_sale">Reach out to Brooke Staggs and do not proceed with social media request. Do NOT submit this form untill all files are confirmed.</label>
                        </div>
                    </div>
                </div> -->
                <?php if(!$isClientRequestAvail) {?>
                <div id="media_consent_form_yes" class="media_consent_form_div">
                    <div class="row pt-2">
                        <div class="col-md-6">
                            <label for="patient_name">Patient Name</label>
                            <input type="input" class="form-control" id="patient_name" name="patient_name" value="{{$clientDetail->ClientNameCustom}}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for=""> City, State</label>
                            <input type="input" class="form-control" name="city_state" id="city_state" value="{{$clientDetail->Patient_City}}, {{$clientDetail->Patient_State}}" readonly />
                        </div>
                    </div>
                    <div class="row pt-2">
                        <div class="col-md-6">
                            <label for="">Client Zip Code</label>
                            <input type="input" class="form-control" name="zip" id="zip" value="{{$clientDetail->Patient_Zip}}" readonly />
                        </div>
                        <div class="col-md-6">
                            <label for="">Discipline</label>
                            <input type="input" class="form-control" name="discipline" id="discipline" value="{{$clientDetail->CF_ClinicianDiscipline}}" />
                        </div>
                    </div>
                    <div class="row pt-2">
                        <div class="col-md-6">
                            <label for="">Skills & Diagnosis</label>
                            <input type="input" class="form-control" name="skills_diagnosis" id="skills_diagnosis" value="{{$clientDetail->CF_Skills}}"/>
                        </div>
                    </div>
                    <div class="row pt-2">
                        <div class="col-md-12">
                            <label for="shifts_needed"> Shifts Needed (Days and Hours). Please make a note if any shifts are flexible.</label>
                            <input type="text" class="form-control" name="shifts_needed" id="shifts_needed" style="width: 65%;" value="{{$scheduleDescription}}" />
                        </div>
                    </div>
                    <div class="row pt-2">
                        <div class="col-md-12">
                            <label for="the_sale">The "Sale". Should include a description of client's personality and activities the client enjoys.</label>
                            <textarea class="form-control" id="the_sale" style="width: 65%;" name="the_sale">{{$clientDetail->CF_TheSale}}</textarea>
                        </div>
                    </div>
                    <div class="row pt-2">
                        <div class="col-md-12">
                            <span class="font-weight-bold pt-2 pb-2">By clicking "Submit" I confirm the schedule listed is accurate and can be advertised on social media platforms</span>
                        </div>
                    </div>
                    <div id="candidate_save" class="row pt-2">
                        <div class="col-md-7 text-right">
                            <button type="button" onclick="setSchedule(this)" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>
<script type="text/javascript">
    function closeForm() {
        $('#nthsocialmedia_form').modal('hide');
    }

    function setSchedule(button, saveandnew = 0) {
        if ($("#nth_socialmedia_form").valid() == false) {
            return false;
        }
        button.disabled = true;
        $.ajax({
            url: "{{ route('dashboard.saventhsocialmedia') }}",
            type: "POST",
            data: $("#nth_socialmedia_form").serialize(),
            success: function(response) {
                button.disabled = false;
                if (response) {
                    $("#successBody").html(response.message);
                    if (response.success == 1) {
                        $("#nthSocialMediaFormDiv").hide();
                        $("#nthSocialMediaSuccessDiv").show();
                        $("#nth_socialmedia_form")[0].reset();
                    } else {
                        $("#nthSocialMediaFormDiv").show();
                        $("#nthSocialMediaSuccessDiv").hide();
                    }

                }
            },
        });
    }
</script>

<script type="text/javascript">
    $(document).ready(function() {
        // $('.media_consent_form').on('click', function() {
        //     $('.media_consent_form_div').hide();
        //     $('#media_consent_form_' + $(this).val()).show();
        // });

        $('#nthsocialmedia_form').on('shown.bs.modal', function() {
            $("#nthSocialMediaFormDiv").show();
            $("#nthSocialMediaSuccessDiv").hide();
        });

        $('#nthsocialmedia_form').on('hidden.bs.modal', function() {
            type = $(this).find('form input[name="type"]').val()
            $('.select-all-' + type + '.td').prop('checked', false);
            $('#select-all-checkbox-' + type).prop('checked', false);
            $('#dataTable-nthchecklist-' + type + '_wrapper').find('.updateall').remove();
        });
    });
</script>
