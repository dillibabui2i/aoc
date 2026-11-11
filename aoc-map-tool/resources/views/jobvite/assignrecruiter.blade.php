<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $recruiters = H::getHelper("ConstantHelper")->getRecruiters('id');
?>
<x-modal>
    <x-slot name="id">assignjobrecruiter</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="title">Update Candidate Recruiter</x-slot>
    <x-slot name="extraHeader">
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 tracker-details">
            <?php if(H::canPermissions(['jobvite_assign_recruiter','is_recruiting_team_leader'])) { ?>
                <form class="scaffold-form" id="assignrecruiter">
                    @csrf
                    <input type="hidden" name="candidates" id="candidates" value="{{implode(",",$candidateIds)}}"/>
                    <ul id="error-note"></ul>
                    <div class="row pt-2">
                        <div class="col-md-6">
                            <label for="recruiter">Current Recruiter:</label>
                            {{$current_recruiter}}
                        </div>
                    </div>

                    <div class="row pt-2">
                        <div class="col-md-6">
                            <label for="recruiter">Update Candidate to the selected recruiter</label>
                            <select id="recruiter" name="recruiter" class="form-control" autocomplete="off">
                                <option value="">-- Select One --</option>
                                <?php foreach($recruiters as $recruiter) { ?> 
                                    <option value="<?php echo $recruiter['value']?>"><?php echo $recruiter['text']?></option>
                                <?php }?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row pt-5" id="saveStatusChangeOutcomeBtnDiv">
                        <div class="col-md-7 text-right">
                            <button type="button" id="saveStatusChangeOutcomeBtn" onclick="setRecruiters(this)"  class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </form>
            <?php } else { ?>

                     <div class="row pt-2">
                        <div class="col-md-12">
                            You don't have permission for update candidate recruiter
                        </div>
                    </div>

                <?php } ?>

            </div>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script>

function setRecruiters(button){
    if($('#assignrecruiter').valid()){
        button.disabled = true;
        $.ajax({
            url: "{{ route('jobvite.saverecruiter') }}",
            type: "POST",
            data: $("#assignrecruiter").serializeObject(), //only input
            success: function(response) {

                if (response.success) {
                    button.disabled = false;
                    closeForm();
                }
            },
        });
    }
}
function closeForm() {
    $('#assignjobrecruiter').modal('hide');
    if($.fn.dataTable.isDataTable('#dataTable-jobvite-NEW')){
        $('#dataTable-jobvite-NEW').DataTable().ajax.reload();
        $('#dataTable-jobvite-NEW_wrapper div.updateall').remove();
    }
    if($.fn.dataTable.isDataTable('#dataTable-jobvite-recruiter_phone_screen')){
        $('#dataTable-jobvite-recruiter_phone_screen').DataTable().ajax.reload();
        $('#dataTable-jobvite-recruiter_phone_screen_wrapper div.updateall').remove();
    }

    if($.fn.dataTable.isDataTable('#dataTable-jobvite-jpet')){
        $('#dataTable-jobvite-jpet').DataTable().ajax.reload();
        $('#dataTable-jobvite-jpet_wrapper div.updateall').remove();
    }

    if($.fn.dataTable.isDataTable('#dataTable-jobvite-pending_graduation_followup')){
        $('#dataTable-jobvite-pending_graduation_followup').DataTable().ajax.reload();
        $('#dataTable-jobvite-pending_graduation_followup_wrapper div.updateall').remove();
    }

    if($.fn.dataTable.isDataTable('#dataTable')){
        $('#dataTable').DataTable().ajax.reload();
    }
    
    
}
$(function()
    {
        $('#assignrecruiter').validate(
        {   rules:
            {   recruiter:{ required:true},
            },
        });
    }
);

</script>
