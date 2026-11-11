<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $outcomes = config('constant')['clinicianNoLongerOnCaseOutcome'];
?>
<style>
    .w40{
        width:40%
    }
</style>
<x-modal>
    <x-slot name="id">clinicianNoLongerOnCaseNextStepForm</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="title">Clinician No Longer On Case \ {{$clinicianDetails->fullName}}</x-slot>
    <x-slot name="extraHeader">
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 tracker-details">
                <div class="col-md-12 card-cnt p-0 d-flex">
                    <div class="card mr-4 col-md-12">
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
                </div>

                <form class="scaffold-form" id="clinicianNoLongerOnCaseOutcomeForm">
                    @csrf
                    <ul id="error-note"></ul>
                    <!-- hidden fields -->
                    <input type="hidden" name="id" id="id" value="{{$clinicianDetails->id}}" />
                    <input type="hidden" name="outcome_key" id="outcome_key" value="" />
                    <div class="row pt-2">
                        <div class="col-md-12">
                            <label for="outcome">Outcome</label>
                            <select id="outcome" name="outcome" class="form-control" autocomplete="off">
                                <option value="">-- Select One --</option>
                                @foreach ($outcomes as $item)
                                <option data-bucket-key={{str_replace('-',"_",Str::slug($item))}} {{isset($defaultOutcome) && $defaultOutcome == $item ? "selected='selected'" : ""}}
                                    value="{{  $item }}">{{ $item }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="outcomeForm">
                        <div class="col-md-12 p-0 m-0 hide suboutcome" id="commonfields">
                            <div id="completed_established" class="col-md-12 pt-2 m-0 p-0">
                                <div class="col-md-12 m-0 p-0">
                                    <label class="w-100">Recommended Clients</label>
                                    <textarea name="comment" id="comment"  class="form-control"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row pt-5 hide" id="saveStatusChangeOutcomeBtnDiv">
                        <div class="col-md-7 text-right">
                            <button type="button" id="saveStatusChangeOutcomeBtn" onclick="setOutcomeForm(this)"  class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script>
$(document).ready(function() {
    $(document).on('change','#clinicianNoLongerOnCaseOutcomeForm #outcome',function(e){
        var outcome = $(this).find(':selected').attr('data-bucket-key');
        $('#commonfields').addClass("hide");
        $("#outcome_key").val(outcome);
        if(outcome=='review_new_clients') {
            $('#commonfields').removeClass("hide");
        }
        $('#saveStatusChangeOutcomeBtnDiv').removeClass("hide");
    });
});
function setOutcomeForm(button){
    if($('#clinicianNoLongerOnCaseOutcomeForm').valid()){
        //console.log($('#clinicianNoLongerOnCaseOutcomeForm').serializeObject());
        button.disabled = true;
        $.ajax({
            url: "{{ route('ccc.saveClinicianNoLongerOnCaseOutcomeForm') }}",
            type: "POST",
            data: $("#clinicianNoLongerOnCaseOutcomeForm").serializeObject(), //only input
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
    $('#clinicianNoLongerOnCaseNextStepForm').modal('hide');
    $('#dataTable-clinician_no_longer_on_case').DataTable().ajax.reload();
}
$(function()
    {
        $('#clinicianNoLongerOnCaseOutcomeForm').validate(
        {   rules:
            {
                comment:{ required:function(element){
                    return $("#clinicianNoLongerOnCaseOutcomeForm #outcome option:selected").attr('data-bucket-key') == "review_new_clients";
                }}

            },
        });
    }
);

</script>
