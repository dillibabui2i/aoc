<?php
    use App\Helpers\H;

    $NoContactData = isset($followupData[$type])?$followupData[$type]:[];
?>

<div class="original-info-container">
    <div class="form-group row">
        <div class="col-md-12">
            <strong>Caller Informantion</strong>
            <p class="m-0 p-0">{{$originalCallDetails->callerFirstName}} {{$originalCallDetails->callerLastName}}, {{$originalCallDetails->callerPhone}}, {{$originalCallDetails->callerEmail}} </p>
        </div>

        <div class="col-md-12 mt-3">
            <strong>Body System(s) Affected</strong>
            <p class="m-0 p-0"><?php echo str_replace(",",", ",$originalCallDetails->bodySystem)?></p>
        </div>

        <div class="col-md-12 mt-3">
           <strong>Symptoms:</strong>
           <p class="m-0 p-0"><?php echo str_replace(",", ",<br>",$originalCallDetails->reason)?></p>
        </div>

        <div class="col-md-12 mt-3">
            <strong>Suggested Disposition:</strong>
            <p class="m-0 p-0"><?php echo $originalCallDetails->disposition?> <?php echo !empty($originalCallDetails->suggestedDatetime)?H::getFormatedDateTime($originalCallDetails->suggestedDatetime):''?></p>

        </div>

        <div class="col-md-12 mt-3">
            <strong>Description:</strong>
            <p class="m-0 p-0">{{$originalCallDetails->description}}</p>
        </div>
    </div>
</div>
<div class="form-group row">
    <strong>Actions</strong>
</div>
<div class="form-group row">
    <div class="col-md-3">
        <label class="w-100">Action Taken</label>
        <select name="previous_action" id="previous_action" class="form-control">
            @foreach(config('constant')['previous_action'] as $v)
                <option value="{{$v}}" <?php if(isset($NoContactData->previous_action) && $v==$NoContactData->previous_action) { ?> selected="selected" <?php }?>>{{$v}}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="form-group row">
    <div class="col-md-12">
        <label class="w-100">Additional Notes</label>
        <textarea name="previous_additional_notes" id="previous_additional_notes" class="form-control"><?php echo isset($NoContactData->previous_additional_notes)?$NoContactData->previous_additional_notes:''?></textarea>
    </div>
</div>

<div class="form-group row">
    <div class="col-md-12 text-center mt-2">
        <button class="btn btn-primary" onclick="saveOriginalAction(event, this);">Save</button>
        <a class="copy-form-btn" onclick="copyFormToClipboard();">Copy Text from Form</a>
    </div>
</div>

<script>
    function saveOriginalAction(e,button){
        e.preventDefault();
        button.disabled = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        var data = $('#followUpTriageCallForm').serializeObject();
        data['_token'] = _token;
        $.ajax({
            url: "{{route('triageprevious.save')}}",
            type: 'post',
            data: {
                   user_id:$(".followUpTriageCallForm #user_id").val(),
                   triagecall_id:$("#triagecall_id").val(),
                   followupType:$("#followupType").val(),
                   previous_action: $("#previous_action").val(),
                   previous_additional_notes:$("#previous_additional_notes").val(),
                   triagecall_id:$("#triagecall_id").val(),
                   _token: _token
            },
            success: function(data) {
                button.disabled = false;
                isConfirmed = 1;
                isSave = 1;
                $('#followUpTriageCallFormModal').modal('hide');
            }
        });
    }
</script>

