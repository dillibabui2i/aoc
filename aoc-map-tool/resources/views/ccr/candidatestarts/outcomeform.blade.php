<?php
use App\Helpers\LD;
use App\Helpers\H;
?>

@php
$interviewTypeList = DB::table('schedule_types')->get();
$interviewTypeData = [];
if (count($interviewTypeList)) {
$interviewTypeData = json_decode(json_encode($interviewTypeList), true);
}
$outcomeList = [];
$patientStatus = H::getHelper("ConstantHelper")->getMssqlPatientStatus();
    $approvedByList = [];
    $approvedUserList = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates');
    foreach($approvedUserList as $user) {
        $approvedByList[] = $user->name;
    }
$facilities = H::getHelper("ConstantHelper")->getFacilities()->toArray();
$facilities = array_column($facilities,'FacilityName');

$permOutcome=["No Longer on Case","Candidate already established with client","Completed"];
//$outcomeList= array_slice($outcomeList,$attemptCount);

$attemptArray = [];

    if($attemptCount==0) {
        $attemptArray[0] = "Attempt 1"; 
    } 
    if($attemptCount==1) {
        $attemptArray[0] = "Attempt 2";
    } 
    if($attemptCount==2) {
        $attemptArray[0] = "Attempt 3";
    }
$outcomeList = array_merge($attemptArray,$permOutcome);
@endphp
<style>
div.custom-control-right {
    padding-right: 24px;
    padding-left: 0;
    margin-left: 0;
    margin-right: 0;
}

div.custom-control-right .custom-control-label::after {
    right: -1.5rem;
    left: auto;
}

div.custom-control-right .custom-control-label::before {
    right: -2.35rem;
    left: auto;
}

.custom-control-right .custom-control-label {
    line-height: 22px;
}

.schedule-appointment {
    margin-top: 40px;
}

.schedule-appointment fieldset {
    padding: 15px;
    border: 1px solid #ddd;
}
label.pleft {
    padding-left: 17px;
    margin-top: -20px;
    display: inherit;
}
</style>
<x-modal>
    <x-slot name="id">ccmCandidateStartOutcome</x-slot>
    <x-slot name="title"><?php echo $typeTitle?>\ {{$recruiterDetails->Candidate}}</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="extraHeader">
    <div class="col-md-6 text-left pull-left">
            <!-- <a href=""><i class="fa fa-pencil"></i></a> -->
            <!--<a href="javascript:void(0);" id="addScheduleForm"><i class="fa fa-calendar-times-o"></i></a> -->
            <a data-bs-dismiss="modal" href="javascript:void(0);"
                onclick="event.preventDefault();openNotes('{{$recruiterDetails->id}}','tracker','Recruiter Tracker Notes Added')"><i
                class="fa  fa-sticky-note-o"></i></a>
        </div>
        <?php if(!empty($recruitingSchedule->outcome)) { ?>
            <div class="col-md-6 text-right pull-right">
                <span class='btn btn-primary'>
                    {{ nl2br($recruitingSchedule->outcome) }}
                </span>
            </div>
        <?php } ?>
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="modal-body" id="scheduleNextSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" onclick="closeForm()" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div class="modal-body" id="ccmOutcomeFormDiv">
            <form method="post" autocomplete="off" id="ccmoutcomeform" class="scaffold-form"  enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="rsid" name="rsid" value="{{$rsid}}">
                <input type="hidden" id="type" name="type" value="{{$type}}" />
                <div class="row d-flex">
                    <div class="col-md-12 tracker-details">
                        <div class="col-md-12 card-cnt p-0 d-flex">
                            <div class="card mr-5">
                                <div class="card-body">
                                    <h5 class="card-title bg-blue">Candidate</h5>
                                    <div class="detail-section">
                                        <h6 id="candidatename">{{$recruiterDetails->Candidate}}</h6>
                                        <p class="card-text">
                                            {{$recruiterDetails->Clinician_ID}}
                                        </p>
                                        <p class="card-text">
                                        <span class="att-value editable-select"
                                                    data-emptytext='Click to add Branch Location' data-type='select'
                                                    data-url='{{route("editable")}}'
                                                    data-source='<?php echo htmlspecialchars(json_encode($facilities),ENT_QUOTES, 'UTF-8');?>'
                                                    data-pk="{{ $recruiterDetails->id }}" data-name='Branch_Location'
                                                    data-value="{{ $recruiterDetails->Branch_Location }}"
                                                    data-table='recruiterDetails'>{{$recruiterDetails->Branch_Location}}</span>
                                        </p>
                                        <p class="card-text">
                                            @if($recruiterDetails->aoc_provider_id)
                                            {{isset($providerDetails->Phone) ? $providerDetails->Phone : ""}}
                                            @else
                                            {{isset($candidateDetails->homePhone) ? $candidateDetails->homePhone : ""}}
                                            @endif

                                        </p>
                                        <p class="card-text">
                                            @if($recruiterDetails->aoc_provider_id)
                                            {{isset($providerDetails->Mobile) ? $providerDetails->Mobile : ""}}
                                            @else
                                            {{isset($candidateDetails->mobile) ? $candidateDetails->mobile : ""}}
                                            @endif
                                        </p>
                                        <p class="card-text">
                                            @if($recruiterDetails->aoc_provider_id)
                                            {{isset($providerDetails->AOC_Email) ? $providerDetails->AOC_Email : ""}}
                                            @else
                                            {{isset($candidateDetails->email) ? $candidateDetails->email : ""}}
                                            @endif
                                        </p>
                                        <div class="tags-cnt">
                                            @if($recruiterDetails->Skillset != '')
                                            <span class='tag'>{{$recruiterDetails->Skillset}}</span>
                                            @endif
                                            @if($recruiterDetails->Bilingual == 'Yes')
                                            <span class='tag'>Bilingual</span>
                                            @endif
                                        </div>
                                        <ul class="d-flex att-cnt">
                                            <li>
                                                <span class="att-name">Total Hours</span>
                                                <span class="att-value editable-text"
                                                    data-emptytext='Click to add Total Hours' data-type='number'
                                                    data-url='{{route("editable")}}'
                                                    data-pk="{{ $recruiterDetails->id }}" data-name='Total_Hours'
                                                    data-value="{{ $recruiterDetails->Total_Hours }}"
                                                    data-table='recruiterDetails'>{{nl2br($recruiterDetails->Total_Hours)}}</span>
                                            </li>
                                            <li>
                                                <span class="att-name">Pay Rate</span>
                                                $<span class="att-value editable-text" data-inputclass="float"
                                                    data-emptytext='Click to add Pay Rate' data-type='text'
                                                    data-url='{{route("editable")}}'
                                                    data-pk="{{ $recruiterDetails->id }}" data-name='Agreed_Pay_Rate'
                                                    data-table='recruiterDetails'>{{number_format((float)$recruiterDetails->Agreed_Pay_Rate, 2, '.', '')}}</span>
                                            </li>
                                            <li>
                                                <span class="att-name">GP</span>
                                                <span class="att-value editable" data-emptytext='Click to add GP'
                                                    data-type='number' data-url='{{route("editable")}}'
                                                    data-pk="{{ $recruiterDetails->id }}" data-name='GP'
                                                    data-value="{{ $recruiterDetails->GP * 100}}"
                                                    data-table='recruiterDetails'>{{$recruiterDetails->GP * 100}}</span>%
                                            </li>
                                            <li>
                                                <span class="att-name">Approved By</span>
                                                <span class="att-value editable-select"
                                                    data-emptytext='Click to add Approved By' data-type='select'
                                                    data-url='{{route("editable")}}'
                                                    data-pk="{{ $recruiterDetails->id }}" data-name='Approved_By'
                                                    data-value="{{ $recruiterDetails->Approved_By }}"
                                                    data-source='<?php echo htmlspecialchars(json_encode($approvedByList),ENT_QUOTES, 'UTF-8');?>'
                                                    data-table='recruiterDetails'>{{nl2br($recruiterDetails->Approved_By)}}</span>
                                            </li>
                                        </ul>
                                        @if($recruiterDetails->aoc_provider_id==0)
                                        <span class="att-value editable-text"
                                            data-emptytext='Click to assign Clinician ID' data-type='text'
                                            data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                            data-name='Clinician_ID' data-value="{{ $recruiterDetails->Clinician_ID }}"
                                            data-table='recruiterDetails'>{{$recruiterDetails->Clinician_ID}}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title bg-blue">Client</h5>
                                    <div class="detail-section">
                                        @if(isset($patientsDetails->Patient_FirstName) ||
                                        isset($patientsDetails->Patient_MiddleName) ||
                                        isset($patientsDetails->Patient_LastName))
                                        <h6 id="clientname">{{$patientsDetails->Patient_FirstName.' '.$patientsDetails->Patient_MiddleName.' '.$patientsDetails->Patient_LastName}}
                                        </h6>
                                        @endif
                                        <p class="card-text">
                                            {{isset($patientsDetails->PatientAccount) ? $patientsDetails->PatientAccount : ""}}
                                        </p>
                                        <p class="card-text">
                                            {{isset($branchDetails->BranchName) ? $branchDetails->BranchName : ""}}
                                        </p>
                                        <p class="card-text">
                                            {{isset($patientsDetails->Patient_Phone) ? $patientsDetails->Patient_Phone : ""}}
                                        </p>
                                        <p class="card-text">
                                            {{isset($patientsDetails->Patient_MobilPhone) ? $patientsDetails->Patient_MobilPhone : ""}}
                                        </p>
                                    </div>
                                    <p class="card-text payer">
                                        @if($recruiterDetails->Payor_Source!='') Payer:
                                        {{$recruiterDetails->Payor_Source}} @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row pt-2">
                    <div class="col-md-6">
                        <label for="outcome">Outcome</label>
                        <select id="outcome" name="outcome" class="form-control" autocomplete="off">
                            <option value="">-- Select One --</option>
                            @foreach ($outcomeList as $item)
                                <option value="{{$item}}">{{$item}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <section class="hide pt-1 pl-0" id="contact_attempt">            
                    <div class="col-md-12 pl-0">
                        <label class="w-100">Comment</label>
                        <textarea name="contact_comment" id="contact_comment"  class="form-control"></textarea>
                    </div>
                </section>
                <div class="col-md-12 p-0 m-0 hide" id="commonfields">
                    

                    <div id="no-longer-on-case" class="col-md-12 hide">
                        <div class="row pt-2">
                            <label for="no_longer_reason">Reason</label>
                            <select id="no_longer_reason" name="no_longer_reason" class="form-control" autocomplete="off">
                                <option value="">-- Select Option --</option>
                                <option value="Placed on new case">Placed on new case</option>
                                <option value="Looking for a new case">Looking for a new case</option>
                                <option value="Left agency">Left agency</option>
                                <option value="Not eligible for other cases">Not eligible for other cases</option>
                            </select>
                        </div>
                    </div>

                    <div id="left_agency_reason" class="col-md-12 hide">
                        <div class="row pt-2">
                            <label for="nurse_left_agency_reason">Left Agency Reason</label>
                            <select id="nurse_left_agency_reason" name="nurse_left_agency_reason" class="form-control" autocomplete="off">
                                <option value="">-- Select One --</option>
                                @foreach(config('constant')['ReasonNurseDeclined'] as $k => $v)
                                <option value="{{$v}}">{{$v}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="completed" class="col-md-12 hide">
                        <div class="row pt-2">
                            <label for="all_supplies_needed_present_in_the_home">All supplies needed present in the home?</label>
                            <select id="all_supplies_needed_present_in_the_home" name="all_supplies_needed_present_in_the_home" class="form-control" autocomplete="off">
                                <option value="">-- Select Option --</option>
                                <option value="Yes, all supplies in home">Yes, all supplies in home</option>
                                <option value="No, additional supplies sent to home">No, additional supplies sent to home</option>
                            </select>
                        </div>

                        <div class="row pt-2">
                            <label for="additional_training_needed">Additional training needed?</label>
                            <select id="additional_training_needed" name="additional_training_needed" class="form-control" autocomplete="off">
                                <option value="">-- Select Option --</option>
                                <option value="No additional training needed">No additional training needed</option>
                                <option value="Yes, additional in-home training scheduled">Yes, additional in-home training scheduled</option>
                                <option value="Yes, additional in office training scheduled">Yes, additional in office training scheduled</option>
                                <option value="Yes, additional virtual training scheduled">Yes, additional virtual training scheduled</option>

                            </select>
                        </div>

                        <div class="row pt-2">
                            <label for="any_questions_about_the_plan_of_care">Any questions about the plan of care?</label>
                            <select id="any_questions_about_the_plan_of_care" name="any_questions_about_the_plan_of_care" class="form-control" autocomplete="off">
                                <option value="">-- Select Option --</option>
                                <option value="No questions">No questions</option>
                                <option value="All questions answered">All questions answered</option>
                                <option value="In person review of POC scheduled">In person review of POC scheduled</option>
                            </select>
                        </div>

                        <div class="row pt-2">
                            <label for="kantime_charting">KanTime charting</label>
                            <select id="kantime_charting" name="kantime_charting" class="form-control" autocomplete="off">
                                <option value="">-- Select Option --</option>
                                <option value="Nurse is charting appropriately">Nurse is charting appropriately</option>
                                <option value="Nurse re-educated on KT charting">Nurse re-educated on KT charting</option>
                                <option value="Nurse scheduled for in office KT training">Nurse scheduled for in office KT training</option>
                                <option value="Nurse schedule for virtual KT training">Nurse schedule for virtual KT training</option>
                            </select>
                        </div>

                        <div class="row pt-2">
                            <label for="aware_of_contact_info_for_ccm_ccc_afterhours">Aware of contact info for CCM, CCC, Afterhours?</label>
                            <select id="aware_of_contact_info_for_ccm_ccc_afterhours" name="aware_of_contact_info_for_ccm_ccc_afterhours" class="form-control" autocomplete="off">
                                <option value="">-- Select Option --</option>
                                <option value="Yes, aware of how to contact office">Yes, aware of how to contact office</option>
                                <option value="No, contact information provided to nurse">No, contact information provided to nurse</option>
                                
                            </select>
                        </div>
                    </div>

                    <div id="completed_established" class="col-md-12 pt-2 m-0 p-0 hide">
                        <div class="col-md-12 pl-0">
                            <label class="w-100">Comment</label>
                            <textarea name="copy_clipboard_text" id="copy_clipboard_text"  class="form-control"></textarea>
                        </div>
                    </div>

                    <div id="candidate_save" class="row pt-2">
                        <div class="col-md-12 text-center">
                            <button type="button" id="candidateSaveBtn" onclick="setOutcomeForm(this)" class="btn btn-primary">Save</button>
                            <a class="copy-form-btn" onclick="copyFormToClipboard();">Copy Text from Form</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>

<style>
.ui-autocomplete {
    z-index: 999999;
}
</style>

<script>

function copyFormToClipboard(){
        var str = '';
        var namestring = ''; 
        namestring += 'Client Name:'+' '+$.trim($("#clientname").html())+'\n';
        namestring += 'Clinician Name:'+' '+$.trim($("#candidatename").html())+'\n';
        var outcome = $("#outcome").find(":selected").text();
            
        $("#ccmoutcomeform select").each(function(ind,elm){
            
            if(outcome=="Attempt 1" || outcome=='Attempt 2' || outcome=='Attempt 3' || outcome=='Candidate already established with client') {
                
                str= $("#outcome").siblings('label').html()+': '+$("#outcome").val() + '\n';
                
            } else if(outcome=='No Longer on Case') {
                str= $("#outcome").siblings('label').html()+': '+$("#outcome").val() + '\n'+ $("#no_longer_reason").siblings('label').html()+': '+$("#no_longer_reason").val() + '\n';
            }  else {
                
                if($(elm).attr("id")!='no_longer_reason') {
                    str += $(elm).siblings('label').html()+': '+$(elm).val() + '\n';
                }
            }
        });
        
        if(outcome=='Candidate already established with client' || outcome=='Completed') {
            str += $("#copy_clipboard_text").siblings('label').html()+': '+$("#copy_clipboard_text").val() + '\n';
        }
        str = namestring+str;
        navigator.clipboard.writeText(str).then(function() {
            console.log('Copied!');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }

    var  typeId ='<?php echo $type?>';
    $(document).on("change","#outcome",function(){
        
        var outcome = $(this).find(":selected").val();
        $("#no-longer-on-case").addClass("hide");
        $("#completed").addClass("hide");
        $("#ccmoutcomeform #contact_attempt").addClass("hide");
        if(outcome=='') {
            $("#commonfields").addClass("hide");
            $("#ccmoutcomeform #contact_attempt").addClass("hide");
        } else {
            $("#commonfields").removeClass("hide");
            if(outcome=='No Longer on Case') {
                $("#no-longer-on-case").removeClass("hide");
                $("#ccmoutcomeform #contact_attempt").addClass("hide");
                //$("#completed").addClass("hide");
            } else if(outcome=='Completed') {
                $("#completed").removeClass("hide");
                $("#ccmoutcomeform #contact_attempt").addClass("hide");
            }else if(outcome=='Attempt 1' || outcome=='Attempt 2' || outcome=='Attempt 3') {
                    $("#ccmoutcomeform #contact_attempt").removeClass("hide");
            }
        }
        $("#completed_established").addClass("hide");
        if(outcome=='Completed' || outcome=='Candidate already established with client') {
            $("#completed_established").removeClass("hide");
        }
    });

    $(document).on("change","#no_longer_reason",function(){
        $("#left_agency_reason").addClass("hide");
        var selectedValue = $("#no_longer_reason").find(":selected").val();
        
        if(selectedValue=='Left agency') {
            
            $("#left_agency_reason").removeClass("hide");
        } 
    });

    function setOutcomeForm(button){
        if($('#ccmoutcomeform').valid()){
            button.disabled = true;
            var outcomeSelected = $("#ccmoutcomeform #outcome").val();
            
            $.ajax({
                url: "{{ route('ccm.candidatestartoutcomesave') }}",
                type: "POST",
                data: $("#ccmoutcomeform").serialize(), //only input
                success: function(response) {
                    
                    if (response) {
                        
                        button.disabled = false;
                        if (response.success == 1) {
                            $("#successBody").html(response.message);
                            $("#ccmOutcomeFormDiv").hide();
                            $("#scheduleNextSuccessDiv").show();
                            if(outcomeSelected=='Candidate already established with client' || outcomeSelected=='Completed') {
                                startConfetti();
                                    setTimeout(function() {
                                        stopConfetti();
                                    }, 1000);
                            }
                            @if(LD::checkFeatureFlag('add-progress-bar-in-ccm-follow-up'))
                            if(type == 'first_shift_follow_up' || type == 'seven_day_follow_up' || type == 'thirty_day_follow_up'){
                                let _token = $('meta[name="csrf-token"]').attr('content');
                                $.ajax({
                                    url: "{{route('ccc.progressbar')}}",
                                    type: 'POST',
                                    dateType:"html",
                                    data: {
                                        bucketType:type,
                                        _token: _token,
                                        filters: $("#ccmcandidatestarts").serializeArray()
                                    },
                                    success: function(res) {
                                        
                                        $(`#${type}_progressbar`).html(res.html);

                                        /*if(res.percentage==100) {
                                            startConfetti();
                                            setTimeout(function() {
                                                stopConfetti();
                                            }, 1000);
                                        }*/
                                    }
                                });
                            }
                            @endif
                        } else {
                            $("#successBody").html(response.message);
                            $("#scheduleNextSuccessDiv").show();
                            $("#ccmOutcomeFormDiv").show();
                        // $("#ccr_sns_form").hide();
                        }


                    }
                },
                error: function() {
                    button.disabled = false;
                },
            });
        }
    }

    function closeForm() {
        $('#ccmCandidateStartOutcome').modal('hide');
        $('#dataTable').DataTable().ajax.reload();
    }

    $(function()
        {
            $('#ccmoutcomeform').validate(
            {   rules:
                { 
                    no_longer_reason:{ required:function(element){
                        return $("#type").val() == "first_shift_follow_up" && $("#outcome option:selected").val() == "No Longer on Case";
                    }},
                    all_supplies_needed_present_in_the_home:{ required:function(element){
                        return $("#outcome option:selected").val() == "Completed";
                    }},
                    additional_training_needed:{ required:function(element){
                        return $("#outcome option:selected").val() == "Completed";
                    }},
                    any_questions_about_the_plan_of_care:{ required:function(element){
                        return $("#outcome option:selected").val() == "Completed";
                    }},
                    kantime_charting:{ required:function(element){
                        return $("#outcome option:selected").val() == "Completed";
                    }},
                    aware_of_contact_info_for_ccm_ccc_afterhours:{ required:function(element){
                        return $("#outcome option:selected").val() == "Completed";
                    }},
                    contact_comment:{ required:function(element){
                        return $("#ccmoutcomeform #outcome option:selected").val() == "Attempt 1" || $("#ccmoutcomeform #outcome option:selected").val() == "Attempt 2" || $("#ccmoutcomeform #outcome option:selected").val() == "Attempt 3";
                    }},

                    copy_clipboard_text:{ required:function(element){
                        return $("#ccmoutcomeform #outcome option:selected").val() == "Completed" || $("#ccmoutcomeform #outcome option:selected").val() == "Candidate already established with client";
                    }},
                    
                },
            });
        }
    );

</script>
