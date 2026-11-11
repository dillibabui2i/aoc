<?php
use App\Helpers\LD;
?>
<style>
.error-field {
    border: 2px solid red;
}
</style>
@if(LD::checkFeatureFlag('add-reason-field-in-supervisory-visit-delete-pop-up'))
<x-modal>
    <x-slot name="id">supervisoryVisit</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Confirmation</x-slot>
    <x-slot name="extraHeader"></x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
            <h5 id="confirmationDialogMessage">Are you sure you want to delete this supervisory visit?</h5>
            <form class="form-group" action="" id="deletesupervisoryVisit">
                <input type="hidden" name="supervisoryVisit" id="supervisoryVisitId" value="">
                <input type="hidden" name="supervisoryVisittype" id="supervisoryVisittype" value="">
                <label for="delete_reason">Reason</label>
                <textarea name="delete_reason" class="form-control" id="delete_reason"></textarea>
            </form>
    </x-slot>
    <x-slot name="footer">
        <button class="btn btn-primary" id="seeccrYesButton" onclick="seeccrYesButton">Yes</button>
        <button class="btn btn-secondary" id="seeccrNoButton" onclick="closesupervisoryVisit()">No</button>
    </x-slot>
</x-modal>
@else
    <x-modal>
        <x-slot name="id">supervisoryVisit</x-slot>
        <x-slot name="size">w25</x-slot>
        <x-slot name="title">Confirmation</x-slot>
        <x-slot name="extraHeader"></x-slot>
        <x-slot name="class"></x-slot>
        <x-slot name="body">
            <div class="row">
                <form class="row" action="" id="deletesupervisoryVisit">
                    <input type="hidden" name="supervisoryVisit" id="supervisoryVisitId" value="">
                    <input type="hidden" name="supervisoryVisittype" id="supervisoryVisittype" value="">
                </form>
                <h5 id="confirmationDialogMessage">Are you sure you want to delete this supervisory visit?</h5>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button class="btn btn-primary" id="seeccrYesButton" onclick="deletesupervisoryVisit()">Yes</button>
            <button class="btn btn-primary" id="seeccrNoButton" onclick="closesupervisoryVisit()">No</button>
        </x-slot>
    </x-modal>
@endif
<script>
$(document).ready(function() {
    $('#deletesupervisoryVisit').validate({
        rules: {
            delete_reason: {
                required: true
            }
        },
        errorClass: 'error-field',
        validClass: 'valid-field',
        errorPlacement: function() {
        },
        highlight: function(element) {
            $(element).addClass('error-field').removeClass('valid-field');
        },
        unhighlight: function(element) {
            $(element).removeClass('error-field').addClass('valid-field');
        },
        submitHandler: function(form) {
            deletesupervisoryVisit();
        }
    });
    $('#seeccrYesButton').click(function() {
        $('#deletesupervisoryVisit').submit();
    });
});
</script>