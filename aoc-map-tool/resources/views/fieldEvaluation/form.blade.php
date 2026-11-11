<?php 
    use App\Helpers\H;
    use App\Helpers\LD;
?>
<x-modal>
    <x-slot name="id">fieldEvaluationFormModal</x-slot>
    <x-slot name="size">w60</x-slot>
        <x-slot name="title">@if(H::isTherapyEvaluation($clinicianData->DisciplineName)) Therapy Evaluation @else Performance Evaluation @endif</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    <form id="fieldEvaluationForm" class="fieldEvaluationForm">
        <input type="hidden" id="id" name="id" value="{{$fieldEvaluationData->id}}"/>
        <input type="hidden" id="provider_id" name="provider_id" value="{{$clinicianData->id}}"/>
        <input type="hidden" name="is_therapy_evaluation" id="is_therapy_evaluation" value="<?php echo H::isTherapyEvaluation($clinicianData->DisciplineName)?>" />
        <input type="hidden" name="branch_state" id="branch_state" value="<?php echo $clinicianData->branchState?>" />
        
        @if(H::isTherapyEvaluation($clinicianData->DisciplineName))
            @if(LD::checkFeatureFlag('therapy-evaluation-changes'))
                @include('fieldEvaluation._therapyevaluationnew')
            @else
                @include('fieldEvaluation._therapyevaluation')
            @endif
        @else
            @include('fieldEvaluation._fieldevaluation')
        @endif
        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                
                @if(H::isTherapyEvaluation($clinicianData->DisciplineName))
                    <button class="btn btn-primary submit" onclick="submitFieldEvaluations(event, this);">Submit</button>
                    <button class="btn btn-primary submit" onclick="submitFieldEvaluations(event, this, 'save');">Save</button>
                    <small class="form-text text-muted" style="width: 100%;">Note: Submitting this form will automatically generate the document and send for signature via RightSignature.</small>
                @else
                    @if(LD::checkFeatureFlag('field-evaluation-co-send-evaluation'))
                        @if($clinicianData->branchState=='CO')
                            <button class="btn btn-primary submit" onclick="submitFieldEvaluations(event, this);">Submit</button>
                            <button class="btn btn-primary submit" onclick="submitFieldEvaluations(event, this, 'save');">Save</button>
                            <small class="form-text text-muted" style="width: 100%;">Note: Submitting this form will automatically generate the document and send for signature via RightSignature.</small>
                        @else
                            <button class="btn btn-primary submit" onclick="submitFieldEvaluations(event, this, 'save');">Submit</button>
                            <small class="form-text text-muted" style="width: 100%;">Note: Submitting this form will save your inputs.  The document will not be sent for signature.</small>
                        @endif
                    @else
                        <button class="btn btn-primary submit" onclick="submitFieldEvaluations(event, this, 'save');">Submit</button>
                        <small class="form-text text-muted" style="width: 100%;">Note: Submitting this form will save your inputs.  The document will not be sent for signature.</small>
                    @endif
                @endif
            </div>
        </div>
    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

@if(LD::checkFeatureFlag('eval-warning-enhancement'))
<x-modal>
    <x-slot name="id">therapyEvaluationConfirmationModal</x-slot>
    <x-slot name="size">w35</x-slot>
        <x-slot name="title">Confirm</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <h5>Are you finished editing this evaluation?<h5>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer">
        <div class="row" style="margin:auto;">
            <button class="btn btn-primary" onclick="$('#therapyEvaluationConfirmationModal').modal('hide');">Continue Editing</button>
            <button class="btn btn-primary ml-2" onclick="submitFieldEvaluations(event, this, 'save', true);">Save Progress</button>
            <button class="btn btn-primary ml-2" onclick="submitFieldEvaluations(event, this, 'submit', true);">Send to Employee</button>
        </div>
    </x-slot>
</x-modal>
@endif

<script type="text/javascript">
    @if(LD::checkFeatureFlag('eval-warning-enhancement'))
    $(document).on('submit', '#fieldEvaluationForm', function(){
        if ($('#is_therapy_evaluation').val() == 1) {
            showConfirmationPopup();
            return false;
        }
    });
    $(document).on("hidden.bs.modal", "#therapyEvaluationConfirmationModal", function() {
        $(".modal:visible").length && $(document.body).addClass("modal-open");
    });
    $(document).on("show.bs.modal", "#therapyEvaluationConfirmationModal", function() {
        $('#therapyEvaluationConfirmationModal .close').css('display', 'none');
        $('.modal-backdrop').not('.show').remove();
    });
    function showConfirmationPopup() {
        $('#therapyEvaluationConfirmationModal').modal({
            keyboard: false
        });
        $('#therapyEvaluationConfirmationModal').modal('show');
    }
    @endif
    
    @if(LD::checkFeatureFlag('eval-warning-enhancement'))
    function submitFieldEvaluations(e, button, action = 'submit', popupAction = false) {
        e.preventDefault();
        if (popupAction != true && $('#is_therapy_evaluation').val() == 1 && action == 'submit') {
            showConfirmationPopup();
            return false;
        }
        button.innerHTML = 'Please wait...';
        button.disabled = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        let data = $('#fieldEvaluationForm').serializeArray();
        data.push({name: "_token", value: _token});
        data.push({name: "action", value: action});
        $.ajax({
            url: "{{route('fieldevaluation.save')}}",
            type: 'post',
            data: $.param(data),
            success: function(data) {
                button.disabled = false;
                if (action == 'save') {
                    button.innerHTML = 'Save';
                } else {
                    button.innerHTML = 'Submit';
                }
                if (data.status) {
                    if (popupAction == true) {
                        $('#therapyEvaluationConfirmationModal').modal('hide');
                        setTimeout(function(){ $('#fieldEvaluationFormModal').modal('hide');}, 200);
                    } else {
                        $('#fieldEvaluationFormModal').modal('hide');
                    }
                    $('#dataTable').DataTable().ajax.reload();
                }
            }
        });
    }
    @else
    function submitFieldEvaluations(e, button, action = 'submit') {
        e.preventDefault();
        button.innerHTML = 'Please wait...';
        button.disabled = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        let data = $('#fieldEvaluationForm').serializeArray();
        data.push({name: "_token", value: _token});
        data.push({name: "action", value: action});
        $.ajax({
            url: "{{route('fieldevaluation.save')}}",
            type: 'post',
            data: $.param(data),
            success: function(data) {
                button.disabled = false;
                if (action == 'save') {
                    button.innerHTML = 'Save';
                } else {
                    button.innerHTML = 'Submit';
                }
                if (data.status) {
                    $('#fieldEvaluationFormModal').modal('hide');
                    $('#dataTable').DataTable().ajax.reload();
                    $("#dataTable-clinician-eval-"+tempObject).DataTable().ajax.reload();
                }
            }
        });
    }
    @endif
</script>
