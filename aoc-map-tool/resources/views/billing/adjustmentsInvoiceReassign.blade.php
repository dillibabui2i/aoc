<?php
use App\Helpers\LD;
use App\Helpers\H;

?>
<style>
    .hidden {
        display: none;
    }
    #category-container .multiselect-native-select {
        display: flex;
        flex-direction: column;
    }
    #category-container #category-error {
        order: 2;
    }
</style>
<x-modal>
    <x-slot name="id">adjustmentsInvoiceReassignModal</x-slot>
    <x-slot name="size">w-40</x-slot>
    <x-slot name="title">Invoice</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row pt-2">
            <div class="col-md-10">
                <form action="" id="adjustmentsInvoiceReassignForm" class="form">
                    <input type="hidden" id="selectedId" name="selectedId" value="{{$selectedId}}"/>
                    <input type="hidden" id="invoiceNumber" name="invoiceNumber" data-label="Invoice #" value="{{$invoiceDetails->invoice_number}}"/>
                    @if($canReassign == 1)
                    <div class="form-group row d-flex">
                        <label  class="col-sm-2" for="outcome">Reassign</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="adjustmentsReassign" id="adjustmentsReassign">
                                <option value="">--- Select One ---</option>
                                @foreach ($reassignUsers as $k => $v)
                                <option value="{{ $v['value'] }}">{{ $v['text'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-12 text-center mt-2">
                            <button class="btn btn-primary submit" onclick="saveAdjustmentsInvoiceReassign(event, this);">Save</button>
                            <button class="btn btn-primary" data-dismiss="modal" aria-label="Close">Cancel</button>
                        </div>
                    </div>
                    @else
                    Only selected users can reassign.
                    @endif
                </form>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script>
$(document).ready(function(){
    $("#adjustmentsInvoiceReassignForm").validate({
        ignore: [],
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "adjustmentsReassign":{
                required: true
            }
        }
    });
});
function saveAdjustmentsInvoiceReassign(e, button){
    e.preventDefault();
    if($('#adjustmentsInvoiceReassignForm').valid()){
        button.disabled = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        var data = $('#adjustmentsInvoiceReassignForm').serializeObject();
        data['_token'] = _token;
        $.ajax({
            url: "{{route('billing.saveAdjustmentsInvoiceReassign')}}",
            type: 'post',
            data: data,
            success: function(data) {
                button.disabled = false;
                if(data.status){
                    $('#adjustmentsInvoiceReassignModal').modal('hide');
                    $("#dataTable-invoices_adjustments").DataTable().ajax.reload(null, false);
                }else{
                    console.log('Error:' + data.msg);
                }
            }
        });
    }
}

$(document).ready(function() {
    $('#adjustmentsOutcome').change(function() {
        $('#requestOpReviewDiv').addClass('hidden');
        $('#requestOpsReview').prop('required', false);
        if ($(this).val() === 'Request Ops Review') {
            $('#requestOpReviewDiv').removeClass('hidden');
            $('#requestOpsReview').prop('required', true);
        }
    });
});
</script>
