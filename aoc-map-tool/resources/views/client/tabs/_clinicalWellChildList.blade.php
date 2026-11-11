<?php
    use App\Helpers\H;
    use App\Helpers\LD;
?>
<div class="col-md-12 flex-wrap alert alert-success error-msg" style="display:none" id="saveWellChildSuccessMsg"></div>
<div class="row col-md-12 pt-1">
    <div class="col-md-2 pt-1">
    <button type="button" onclick="addClinicalWellChild();" class="btn btn-primary">Add</button>
    </div>
    <div class="col-md-6 pt-1"><div id="new_dataTable_wrapper"></div></div>
</div>
@php
if(!empty($clinicalWellChildList)) {
@endphp
<div class="col-md-12 p-0 mt-3">
    <table class="table" aria-describedby="">
        <tr>
            <th>Primary Care Physician</th>
            <th>Well Child Visit Date</th>
        </tr>
        @if(count($clinicalWellChildList) > 0)
            @foreach($clinicalWellChildList as $clinicalWellChildListTemp)
            <tr>
                <td>
                    @if(LD::checkFeatureFlag('delete-well-child-visit'))
                    <input type="checkbox" name="checkbox-candidate[]" class="form-input td select-all-candidate" value="{{ $clinicalWellChildListTemp->id }}" />&nbsp;
                    @endif
                    {{ $clinicalWellChildListTemp->fullName }}</td>
                <td>{{ H::getFormatedDate($clinicalWellChildListTemp->last_visit) }}</td>
            </tr>
            @endforeach
        @else
        <tr><td colspan="2">No record found</td></tr>
        @endif
    </table>
</div>
<script>
$(document).ready(function($) {
    $('.select-all-candidate.td').off().bind('click', function() {
        showHideButtonForMassText($('.select-all-candidate.td:checked').length ? true : false);
    });
});
</script>
@php } @endphp
