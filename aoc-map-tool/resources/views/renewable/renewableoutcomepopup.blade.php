<?php
use App\Helpers\LD;
use App\Helpers\H;

$title = H::getHelper("Renewables")->getTitleFromType($type);

$outcome = [];

    if($type=='thirtyzeroexpiring' || $type=='ninetythirtyexpiring') {
        $outcome = H::getHelper("Renewables")->getExpiringOutcome();
    } else {
        $outcome = H::getHelper("Renewables")->getMissingOutcome();
    }


    $attemptArray = [];
     $attemptCount = $renewableData[$type.'_attempt_count'];
    if($attemptCount==0) {
        $attemptArray[0] = "Attempt 1";
    }
    if($attemptCount==1) {
        $attemptArray[0] = "Attempt 2";
    }
    if($attemptCount==2) {
        $attemptArray[0] = "Attempt 3";
    }
$outcomeList = array_merge($attemptArray,$outcome);
?>

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
.multiselect-native-select .btn-group{
    max-width: 100%;
    width: 100%;
}
</style>
<x-modal>
    <x-slot name="id">getrenewableoutcomeform</x-slot>
    <x-slot name="title"><?php echo $title ?> \ {{$clinicianDetails->FirstName}} {{$clinicianDetails->LastName}} </x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="extraHeader">
            <?php if(!empty($renewableData->outcome)) { ?>
                <div class="col-md-12 text-right pull-right p-0">
                    <span class='btn btn-primary'>
                        {{ nl2br($renewableData->outcome) }}
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
        <div class="modal-body" id="renewableoutcomediv">
            <form method="post" autocomplete="off" id="renewableoutcomeform" class="scaffold-form"  enctype="multipart/form-data">
                @csrf

                <input type="hidden" id="type" name="type" value="{{$type}}" />
                <input type="hidden" id="renewable_id" name="renewable_id" value="{{$renewableData->id}}" />
                <div class="row d-flex">
                    <div class="col-md-12 tracker-details">
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
                                    <h5 class="card-title bg-blue">Renewables</h5>
                                    <div class="detail-section">
                                        <h6>
                                            {{$renewableData->name}}
                                        </h6>
                                        <p class="card-text">
                                            {{$renewableData->description}}
                                        </p>
                                        <p class="card-text">
                                            {{H::getFormatedDate($renewableData->expiration_date)}}
                                        </p>

                                        <ul class="d-flex att-cnt col-md-12">
                                            <li class="col-md-4 p-0">
                                                {{H::getFormatedDate($renewableData->issued_date)}}
                                            </li>
                                            <li class="col-md-4 p-0">
                                                {{$renewableData->license_no}}
                                            </li>
                                            <li class="col-md-4 p-0">
                                                {{$renewableData->license_state}}
                                            </li>
                                        </ul>
                                    </div>
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

                           <?php foreach($outcomeList as $item) { ?>
                                <option value="<?php echo $item?>"><?php echo $item?></option>
                            <?php } ?>

                        </select>
                    </div>
                    <section class="hide pl-0 col-md-6" id="contact_attempt">
                    <label for="outcome">&nbsp;</label>
                        <div class="col-md-12 pl-0">

                                <select id="results" name="results" data-placeholder="Select Result(s)" class="form-control selectpicker" multiple data-live-search="true" autocomplete="off">

                                    <option value="Had Conversation">Had Conversation</option>
                                    <option value="Left Voicemail">Left Voicemail</option>
                                    <option value="Sent Text">Sent Text</option>
                                </select>
                        </div>
                    </section>
                </div>

                    <div class="col-md-12 p-0 m-0 hide" id="commonfields">

                        <div id="completed_established" class="col-md-12 pt-2 m-0 p-0">
                            <div class="col-md-12 pl-0">
                                <label class="w-100">Comment</label>
                                <textarea name="comment" id="comment"  class="form-control"></textarea>
                            </div>
                        </div>
                    </div>



                    <div id="candidate_save" class="col-md-12 p-0 pt-2 m-0 hide">
                        <div class="col-md-12 text-center">
                            <button type="button" id="candidateSaveBtn" onclick="setOutcomeForm(this)" class="btn btn-primary">Save</button>
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

$(document).ready(function() {
    $('#renewableoutcomeform #results').multiselect({
        includeSelectAllOption: true,
    });
});


var  typeId ='<?php echo $type?>';
$(document).on("change","#renewableoutcomeform #outcome",function(){

    var outcome = $(this).find(":selected").val();
    $("#renewableoutcomeform #contact_attempt").addClass("hide");
    $("#renewableoutcomeform #commonfields").addClass("hide");
    $("#renewableoutcomeform #candidate_save").addClass("hide");


    if(outcome!='') {

        if(outcome=='Attempt 1' || outcome=='Attempt 2' || outcome=='Attempt 3') {
                $("#renewableoutcomeform #contact_attempt").removeClass("hide");
                $("#renewableoutcomeform #candidate_save").removeClass("hide");
                $("#renewableoutcomeform #commonfields").removeClass("hide");
        } else {
            $("#renewableoutcomeform #commonfields").removeClass("hide");
            $("#renewableoutcomeform #candidate_save").removeClass("hide");
        }
    }


});



function setOutcomeForm(button){
    if($('#renewableoutcomeform').valid()){
        //console.log($('#renewableoutcomeform').serializeObject());
        button.disabled = true;
        $.ajax({
            url: "{{ route('renewables.saveoutcomeform') }}",
            type: "POST",
            data: $("#renewableoutcomeform").serializeObject(), //only input
            success: function(response) {

                if (response.status) {
                    button.disabled = false;
                    closeForm();
                }
            },
        });
    }
}

function closeForm() {
    $('#getrenewableoutcomeform').modal('hide');
    $('#dataTable-expiring-<?php echo $type?>').DataTable().ajax.reload();
}

$(function()
    {
        $('#renewableoutcomeform').validate(
        {   rules:
            {   comment:{ required:true},
                results:{ required:function(element){
                    return $("#renewableoutcomeform #outcome option:selected").val() == "Attempt 1" || $("#renewableoutcomeform #outcome option:selected").val() == "Attempt 2" || $("#renewableoutcomeform #outcome option:selected").val() == "Attempt 3";
                }},



            },
        });
    }
);

</script>
