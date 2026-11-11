<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $outcomes = config('constant')['clinicianNoLongerOnCaseOutcome'];
?>
<x-modal>
    <x-slot name="id">clinicianNoLongerOnCaseNthScheduleForm</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="title">Clinician impacted by this addition</x-slot>
    <x-slot name="extraHeader">
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <form id="myForm">
            @csrf
            <input type="hidden" name="aoc_nthaccounts_id" id="aoc_nthaccounts_id" value="{{ $aoc_nthaccounts_id }}" />
            <div class="row d-flex">
                <div class="grid-container col-md-12 p-0">
                    <table class="table bg-white" summary="Clinician No longer on case">
                        <tr>
                            <th></th>
                            <th>Clinician</th>
                            <th>Reason</th>
                            <th>Recommended Clients</th>
                        </tr>
                        <?php foreach($nthData as $schedule) { ?>
                        <tr class="rowdata">
                            <td>
                                <input
                                class="checkbox"
                                type="checkbox"
                                name="provider_id[]"
                                value="<?php echo $schedule->ProviderID?>">
                            </td>
                            <td>{{$schedule->fullName}}
                            </td>
                            <td>
                                <select id="outcome"
                                name="outcome[<?php echo $schedule->ProviderID?>]"
                                class="form-control select reasonDropDown" autocomplete="off" disabled>
                                    <option value="">-- Select One --</option>
                                    @foreach ($outcomes as $item)
                                    <option
                                    data-bucket-key={{str_replace('-',"_",Str::slug($item))}}
                                    {{isset($defaultOutcome) && $defaultOutcome == $item ? "selected='selected'" : ""}}
                                        value="{{str_replace('-',"_",Str::slug($item))}}">{{ $item }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text"
                                name="comment[<?php echo $schedule->ProviderID?>]"
                                class="form-control text commentBox" disabled />
                            </td>
                        </tr>
                        <?php }?>
                    </table>
                    <div class="col-md-12 mt-2 text-center">
                        <button type="button"
                        onclick="saveNoLongerOnCase(event)" id="btnSaveNoLongerOnCase"
                        class="btn btn-primary add" disabled>
                            Save
                        </button>
                        &nbsp;
                        <button type="button"
                        class="btn btn-primary add cancelbutton" onclick="closeNoLongerOnCase()">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script>
$(document).ready(function() {

     $(".rowdata").on("change", ".checkbox", function () {
        var $select = $(this).closest(".rowdata").find(".select");
        var $text = $(this).closest(".rowdata").find(".text");
        $select.removeClass('error');
        $text.removeClass('error');
        $select.prop('selectedIndex',0);
        $select.prop("disabled", !this.checked);
        if ($('.checkbox:checked').length > 0) {
          $('#btnSaveNoLongerOnCase').prop('disabled', false);
        } else {
          $('#btnSaveNoLongerOnCase').prop('disabled', true);
        }
        $text.prop("disabled", !$select.val() || !this.checked);
    });

    $(".rowdata").on("change", ".select", function () {
        var $text = $(this).closest(".rowdata").find(".text");
        $text.removeClass('error');
        $text.prop("disabled", !this.value || !$(this).closest(".rowdata").find(".checkbox").prop("checked") || ($(this).find(':selected').attr('data-bucket-key') != 'review_new_clients'));
    });
    jQuery.validator.addClassRules('reasonDropDown', {
        required: true
    });
    jQuery.validator.addClassRules('commentBox', {
        required: true
    });
    $("#myForm").validate({
        errorPlacement: function(error, element) {}
    });
});
function closeNoLongerOnCase() {
    $('#clinicianNoLongerOnCaseNthScheduleForm').modal('hide');
}
function saveNoLongerOnCase(e){
    e.preventDefault();
    let _token = $('meta[name="csrf-token"]').attr('content');
    var data = {};
    fields = $('#myForm').serialize();
    if($('#myForm').valid()){
        data['_token'] = _token;
        $.ajax({
            url: "{{route('ccc.saveNoLongerOnCaseNthSchedule')}}",
            type: 'post',
            data: fields,
            success: function(data) {
                if(data.status) {
                    $('#clinicianNoLongerOnCaseNthScheduleForm').modal('hide');
                }
            }
        });
    }
}
</script>
