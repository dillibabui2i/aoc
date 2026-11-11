<?php
    use App\Helpers\H;
    $loggedInUser = H::getLoggedInUser();
    $currentDate = date('m/d/Y');
    $currentTime = date('H:i');
	$pastSteps = H::getHelper('ConstantHelper')->getPreviousSteps($followType); 
    
?>
<style>
    .followUpTriageCallForm .nav-tabs .nav-item.show .nav-link, .followUpTriageCallForm .nav-tabs .nav-link.active {
        background-color: #2f5baa;
        color: #fff;
    }
    .followUpTriageCallForm .nav-tabs .nav-item.show .nav-link, .followUpTriageCallForm .nav-tabs .nav-link {
        background-color: #ddd;
        color: #000;
    }
    .followUpTriageCallForm li.nav-item {
        margin-right: 10px;
    }
    label#contactWithCaller-error {
        position: absolute;
        width: 200px !important;
        bottom: -35px;
        left: 0px;
    }
    div#order_detail_table_wrapper{
        width:100%;
    }
</style>

<x-modal>
    <x-slot name="id">followUpTriageCallFormModal</x-slot>
    <x-slot name="size">w80</x-slot>
    <x-slot name="title">{{H::getHelper('ConstantHelper')->getTriageFollowupType($followType)}} Follow-Up \ {{$clientDetails->Patient_FirstName.' '.$clientDetails->Patient_LastName}}</x-slot>
    <x-slot name="class">followup-triage-call-form</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    <form id="followUpTriageCallForm" class="followUpTriageCallForm">
        <input type="hidden" name="triagecall_id" id="triagecall_id" value="<?php echo $originalCallDetails->id?>" />
        <input type="hidden" name="followupType" id="followupType" value="<?php echo $followType?>" />
        
        <h5>General</h5>
        <section class="triage-details p-2">
            <div class="form-group row">
                <div class="col-md-12 d-flex">
                    <label  class="w-auto" for="contactWithCaller">Did you get in contact with caller?</label>
                    <div class="custom-control custom-switch custom-switch-lg text-center">                        
                        <input class="custom-control-input" type="checkbox" id="contactWithCaller" value="1" name="contactWithCaller"/>
                        <label class="custom-control-label" for="contactWithCaller"></label>
                        
                    </div>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-4">
                    <label  class="w-100" for="user_id">Reporter</label>
                    <input type="hidden" class="form-control" id="user_id" name="user_id" value="{{$loggedInUser['id']}}"/>
                    <input type="text" class="form-control disabled general" id="reporter" name="reporter" value="{{$loggedInUser['name']}}" disabled/>
                    <p class="original">{{$userName}}</p>
                    
                </div>
                <div class="col-md-4">
                    <label  class="w-100" for="callDate">Date</label>
                    <input type="text" class="form-control datepicker disabled general" id="callDate" name="callDate" value="{{$currentDate}}" disabled/>
                    <p class="original">{{H::getFormatedDate($originalCallDetails->callDate)}}</p>
                </div>
                <div class="col-md-4">
                    <label  class="w-100" for="callStartTime">Call Start Time</label>
                    <input type="time" class="form-control disabled general" id="callStartTime" name="callStartTime" value="{{$currentTime}}" disabled/>
                    <p class="original">{{H::getFormatedTime($originalCallDetails->callStartTime)}}</p>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-3">
                    <label  class="w-100" for="callerFirstName">Caller First Name</label>
                    <input type="text" class="form-control general" id="callerFirstname" name="callerFirstname" value="{{$originalCallDetails->callerFirstName}}"/>
                    <p class="original">{{$originalCallDetails->callerFirstName}}</p>
                </div>
                <div class="col-md-3">
                    <label  class="w-100" for="callerLastName">Caller Last Name</label>
                    <input type="text" class="form-control general" id="callerLastname" name="callerLastname" value="{{$originalCallDetails->callerLastName}}"/>
                    <p class="original">{{$originalCallDetails->callerLastName}}</p>
                </div>
                <div class="col-md-3">
                    <label  class="w-100" for="callerPhone">Caller Phone Number</label>
                    <input type="text" class="form-control general" id="callerPhone" name="callerPhone" value="{{$originalCallDetails->callerPhone}}"/>
                    <p class="original">{{$originalCallDetails->callerPhone}}</p>
                </div>
                <div class="col-md-3">
                    <label  class="w-100" for="callerEmail">Caller Email Address</label>
                    <input type="text" class="form-control general" id="callerEmail" name="callerEmail" value="{{$originalCallDetails->callerEmail}}"/>
                    <p class="original">{{$originalCallDetails->callerEmail}}</p>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-3">
                    <label  class="w-100" for="clientRelationship">Relationship to Client</label>
                   
                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" name="callerRelationship" id=" " <?php echo ($originalCallDetails->clientRelationship=='Parent')?"checked='checked'":"";?>  value="Parent" />
                        <label class="form-check-label" for="clientRelationshipParent">Parent</label>
                    </div>
                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" name="callerRelationship" id="clientRelationshipNurse" <?php echo ($originalCallDetails->clientRelationship=='Nurse')?"checked='checked'":"";?> value="Nurse" />
                        <label class="form-check-label" for="clientRelationshipNurse">Nurse</label>
                    </div>
                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" name="callerRelationship" id="clientRelationshipAuthorizedCaller" <?php echo ($originalCallDetails->clientRelationship=='Authorized Caller')?"checked='checked'":"";?> value="Authorized Caller" />
                        <label class="form-check-label" for="clientRelationshipAuthorizedCaller">Authorized Caller</label>
                    </div>
                    <p class="form-check-label original">{{$originalCallDetails->clientRelationship}}</p>
                </div>
                <div class="col-md-6">
                    <label  class="w-100" for="aoc_patient_id">Client Name</label>
                    <input type="text" readonly class="form-control general" id="clientName" name="clientName" value="{{$clientDetails->Patient_FirstName}} {{$clientDetails->Patient_LastName}}"/>
                    <p class="original">{{$clientDetails->Patient_FirstName}} {{$clientDetails->Patient_LastName}}</p>
                    <input type="hidden" class="form-control" id="aoc_patient_id" name="aoc_patient_id" value="{{$originalCallDetails->aoc_patient_id}}"/>
                    <div id="clientWarningMsg" class="alert alert-warning mt-3 pl-1 pr-1 pt-2 pb-2 hide" role="alert"> <i class="fa fa-exclamation-triangle"></i> We found <span id="clientCallCount">3</span> other Triage Calls for this Client. <i class="fa fa-external-link pull-right"></i></div>
                </div>
                <div class="col-md-3">
                    <label  class="w-100" for="clientDOB">Client Date Of Birth</label>
                    <input type="text" readonly class="form-control datepicker general" id="clientDOB" name="clientDOB" value="{{H::getFormatedDate($originalCallDetails->clientDOB)}}"/>
                    <p class="original">{{H::getFormatedDate($originalCallDetails->clientDOB)}}</p>
                </div>
                
            </div>
        </section>
        <section class="follow-up-form-details">

            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item active">
                    <a class="nav-link active" data-type="general" data-toggle="tab" href="#hoursForm" role="tab"><?php echo H::getHelper('ConstantHelper')->getTriageFollowupType($followType)?> Follow Up</a>
                </li>
				<?php foreach($pastSteps as $key=>$value){ ?>
					<li class="nav-item">
						<a class="nav-link" data-type="form<?php echo $key?>" data-toggle="tab" href="#Form<?php echo $key?>" role="tab"><?php echo $value?> Follow Up</a>
					</li>
				<?php } ?>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" data-type="original" href="#followUpInfo" role="tab">Original</a>
                </li>
            </ul>
            <div class="tab-content p-3">
                <div class="tab-pane active" id="hoursForm" role="tabpanel">
                    @include('triage._followUpDetailsForm')
                </div>

                <?php foreach($pastSteps as $key=>$value){ ?>
					<li class="tab-pane" id="Form<?php echo $key?>" role="tabpanel">
						@include('triage._followUpStepInfo',['type'=>$key])
					</li>
				<?php } ?> 
                <div class="tab-pane" id="followUpInfo" role="tabpanel">
                    @include('triage._followUpInfo',['type'=>$followType])
                </div>
            </div>

        </section>
        
    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script>

    

    $(document).ready(function(){
        $('#callerPhone').usPhoneFormat({
            format: '(xxx) xxx-xxxx',
        });
        $(document).on("change","#additionCoordination",function(){
            if($(this).find(":selected").val()=="Other") {
                $("#additionalCoordinationOther").removeClass("hide");
            } else {
                $("#additionalCoordinationOther").addClass("hide");
            }
        });
        $(document).on("click","#contactWithCaller",function(){
            if($(this).is(":checked")){
                $(".general").show();
                $(".original").hide();
            } else {
                $(".general").hide();
                $(".original").show();
            }
        });

        $(".general").hide();
        $(".original").show();

    });
    function copyFormToClipboard(){
        var str = '';
        $("#followUpTriageCallForm input[type=text],#followUpTriageCallForm input[type=time],#followUpTriageCallForm textarea,#followUpTriageCallForm select").each(function(ind,elm){
            if($(elm).attr('id') == 'bodySystem'){
                str += 'Body System(s) Involved'+': '+$(elm).val() + '\n';
            }else if($(elm).attr('id') == 'reason'){
                str += 'Reason'+': '+$(elm).val() + '\n';
            }else{
                str += $(elm).siblings('label.w-100').html()+': '+$(elm).val() + '\n';
            }
        });
        navigator.clipboard.writeText(str).then(function() {
            console.log('Copied!');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }

    $(document).on("click",".follow-up-form-details .nav-link", function(e){
        if($(this).attr("data-type")=="original") {
            $(".general").hide();
            $(".original").show();
            $('#contactWithCaller').prop('checked', false); 
        } else if($(this).attr("data-type")=='general') {
            $(".general").show();
            $(".original").hide();
            $('#contactWithCaller').prop('checked', true);    
        } else {
            $(".general").hide();
            $(".original").show();
            $('#contactWithCaller').prop('checked', false); 
        }
    });
    var isConfirmed = 0;
    var isSave = 0;
    $('#followUpTriageCallFormModal').on('hide.bs.modal', function(event) {
        (!isConfirmed) ? event.preventDefault():'';
        (!isSave) ? modalConfirm('Do you want to exit without saving? Any unsaved changes will not be saved.','Close without Saving','Go Back',function(res,modelDialog){
            if(res){
                isConfirmed = 1;
                $('.modal').modal('hide');
            }else{
                modelDialog.modal('hide');
                $('body').addClass("modal-open");
            }
        }) : '';
    });

    $('.datepicker').datepicker();
    $('.datepicker').datepicker().bind('click', function() {
        $('#ui-datepicker-div').appendTo($(this).parent());
    });

    $("#followUpTriageCallForm #clientName").autocomplete({
        source: "{{ route('getScheduleClient') }}",
        classes: {
            "ui-autocomplete": "clientName"
        },
        minLength: 2,
        select: function(event, ui) {
            $('#followUpTriageCallForm #aoc_patient_id').val(ui.item.id);
            /*checkingClientCallsAvailability(ui.item.id,function(count){
                if(count > 0){
                    $('#clientWarningMsg').removeClass('hide').find('#clientCallCount').html(count);
                }else{
                    $('#clientWarningMsg').addClass('hide');
                }
            }); */
        }
    }); 

    $(document).on('change','#wasClientHospitalized,#wasMedPrescribed,#wereNewOrderRecieived, #wasDiagnosisMade',function(){
        
        if($(this).is(":checked")){
           $("."+$(this).attr("id")).removeClass("hide");
        } else {
           $("."+$(this).attr("id")).addClass("hide");
        }
        
    });

    function saveTriageFollowUp(e, button){
        e.preventDefault();
        if($('#followUpTriageCallForm').valid()){
            $('.disabled').removeAttr('disabled');
            button.disabled = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#followUpTriageCallForm').serializeObject();
            data['_token'] = _token;
            $.ajax({
                url: "{{route('triagefollowup.save')}}",
                type: 'post',
                data: data,
                success: function(data) {
                    button.disabled = false;
                    if(data.status){
                        isConfirmed = 1;
                        isSave = 1;
                        $('#followUpTriageCallFormModal').modal('hide');

                    }else{
                        console.log('Error:' + data.msg);
                    }
                    $('.disabled').addAttr('disabled');
                }
            });
        }
    }
    $(document).ready(function(){

    $("#followUpTriageCallForm").validate({
        ignore: [],
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "user_id":{
                required: true
            },
            
            "callDate":{
                required: true
            },
            "callStartTime":{
                required: true
            },
            "callerFirstname":{
                required: true
            },
            "callerLastname":{
                required: true
            },
            "callerPhone":{
                required: true,
                phoneNumber: true,
                minlength:14,
            },
            "callerEmail":{
                required: true,
                customEmail:true
            },
            "clientRelationship":{
                required: true
            },
            "finalDisposition":{
                required:true,
            },
            "despositionActionDate":{
                required:true,
            },  
            "hospitalizationStartDate":{
                required: function(element) {
                    return ($("#wasClientHospitalized").is(":checked"));
                }
            },  
            "drugClassification":{
                required: function(element) {
                    return ($("#wasMedPrescribed").is(":checked"));
                }
            },
            "hasMedicationPickedUp":{
                required: function(element) {
                    return ($("#wasMedPrescribed").is(":checked"));
                }   
            },
            "contactWithCaller":{
                required:true,
            },
           
        },
        messages: {
            "callerPhone":{
                minlength: 'Please enter at least 10 numbers.',
            }
        }
        //errorPlacement: function(error, element) {}
    });

    //Custom rules for validation
    $.validator.addMethod("phoneNumber", function(value, element) {
        var regex = /^[0-9- )(]+$/;
        return regex.test(value);
    }, "This field will allow only numbers");

    //Custom rules for validation
    $.validator.addMethod("customEmail", function(value, element) {
        var regex = /^\b[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b$/i;
        return regex.test(value);
    }, "Please enter a valid email address.");
});



</script>
