<?php
use App\Helpers\LD;

if($type == 'invoices_over_paid'){
    $reason = config('constant.billingInvoiceOutcome')['OverpaidInvoices'];
}else{
    $reason = config('constant.billingInvoiceOutcome')['UnpaidInvoices'];
}
$subReason = config('constant.invoice_outcome_subreasons');
$subReasonJson = json_encode($subReason);
$subReasonDetails = config('constant.invoice_outcome_subreason_details');
$jsonDetailsOptions = json_encode($subReasonDetails);

if (LD::checkFeatureFlag('update-adjustment-workflow')) {
    $categoryList = $adjustmentsCategories;
} else {
    $categoryList = config('constant.billingUpdatedOutcomeCategory');
}

$billingCategory = (LD::checkFeatureFlag('update-write-off-request-options')) ? $categoryList : config('constant.billingOutcomeCategory');

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
    <x-slot name="id">billingInvoiceOutcomeModal</x-slot>
    <x-slot name="size">w-40</x-slot>
    <x-slot name="title">Invoice</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <form action="" id="billingInvoiceOutcomeForm" class="form">
            <input type="hidden" id="selectedIds" name="selectedIds" value="{{$selectedIds}}"/>
            <input type="hidden" id="invoiceNumber" name="invoiceNumber" data-label="Invoice #" value="{{$invoiceNumber}}"/>
            <input type="hidden" id="bucket_type" name="bucket_type" value="{{$type}}"/>
            <div class="form-group row d-flex">
            </div>
            <div class="form-group row d-flex">
                <label  class="col-sm-2" for="reason">Reason</label>
                <div class="col-sm-8">
                    <select class="form-control" name="billingInvoiceReason" id="billingInvoiceReason">
                        <option value="">--- Select One ---</option>
                        @foreach ($reason as $k => $v)
                            <option value="{{ $v }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if(LD::checkFeatureFlag('add-category-option-in-billing-outcome'))
                <div id="category-container" class="form-group row hidden">
                    <label for="category" class="col-sm-2">Category</label>
                    <div class="col-sm-8">
                        <select id="category" name="billingInvoiceCategory"  class="form-control">
                            <option value="">Select Category</option>
                            @foreach ($billingCategory as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif
            <div id="sub-reason-container" class="form-group row hidden">
                <label for="sub-reason" class="col-sm-2">Sub Reason</label>
                <div class="col-sm-8">
                    <select id="sub-reason" name="billingInvoiceSubReason"  class="form-control">
                        <option value="">Select Sub Reason</option>
                        @foreach ($subReason as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div id="sub-reason-details-container" class="form-group row hidden">
                <label for="sub-reason-details" class="col-sm-2">Details</label>
                <div class="col-sm-8">
                    <select id="sub-reason-details" name="billingInvoiceSubReasonDetails"  class="form-control">
                        <option value="">Select Details</option>
                    </select>
                </div>
            </div>
            <div class="form-group row d-flex">
                <label  class="col-sm-2" for="reason">Comments</label>
                <div class="col-sm-8">
                    <textarea name="billingInvoiceComments" id="billingInvoiceComments" class="form-control"></textarea>
                </div>
            </div>
            <div class="form-group row" id="errorMsgDiv" style="display: none;">
                <div class="col-md-12 text-center mt-2 error" id="errorMsgBox" style="color: red;">
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-12 text-center mt-2">
                    <a class="copy-form-btn" onclick="copyFormToClipboard();">Copy Text from Form</a>
                    <button class="btn btn-primary submit" onclick="saveBillingInvoiceOutcome(event, this);">Save</button>
                    <button class="btn btn-primary" data-dismiss="modal" aria-label="Close">Cancel</button>
                </div>
            </div>
            <div class="form-group row d-flex">
            </div>
            <div class="form-group row d-flex">
            </div>
        </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script>
$(document).ready(function(){
    $("#billingInvoiceOutcomeForm").validate({
        ignore: [],
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "billingInvoiceReason":{
                required: true
            },
            'billingInvoiceComments':{
                required: true
            }
        }
    });
});
function saveBillingInvoiceOutcome(e, button){
    e.preventDefault();
    if($('#billingInvoiceOutcomeForm').valid()){
        $('.disabled').removeAttr('disabled');
        button.disabled = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        var data = $('#billingInvoiceOutcomeForm').serializeObject();
        data['_token'] = _token;
        $.ajax({
            url: "{{route('billing.saveBillingInvoiceOutcome')}}",
            type: 'post',
            data: data,

            success: function(data) {
                button.disabled = false;
                if(data.status){
                    $('#billingInvoiceOutcomeModal').modal('hide');
                    $('.dataTable').DataTable().ajax.reload(null, false);
                    showHideInvoiceUpdateButtonFor{{$type}}(false);
                }else{
                    console.log('Error:' + data.msg);
                }
                $('.disabled').attr('disabled','disabled');
            },

            error: function(xhr) {
              let message = 'Something went wrong.';

              if (xhr.responseJSON) {
                if (xhr.responseJSON.message) {
                  message = xhr.responseJSON.message;
                } else if (xhr.responseJSON.errors) {
                  message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
              }

              $('#errorMsgBox').html(message);
              $('#errorMsgDiv').show();
            },

            complete: function() {
              button.disabled = false;
              $('.disabled').attr('disabled', 'disabled');
            }

        });
    }
}
function copyFormToClipboard() {
    const subdetailsOptions = {!! $subReasonJson !!};
    var str = '';
    let elements = [
        'invoiceNumber|hidden',
        'billingInvoiceReason|select',
        'billingInvoiceSubReason|select',
        'billingInvoiceSubReasonDetails|select',
        'billingInvoiceCategory|select',
        'billingInvoiceComments|text',
    ]
    $.each ( elements, function (index, elementName) {
        @if(LD::checkFeatureFlag('add-category-option-in-billing-outcome'))
            let elementInput = elementName.split('|');
            let elm = '#billingInvoiceOutcomeForm [name="' + elementInput[0] + '"]';
            if(elm == '#billingInvoiceOutcomeForm [name="invoiceNumber"]'){
                str += $(elm).attr('data-label')+': '+$(elm).val() + '\n';
            } else if(elm == '#billingInvoiceOutcomeForm [name="billingInvoiceSubReason"]'){
                if (!$("#sub-reason-container").hasClass("hidden")) {
                    str += $(elm).parent().siblings('label').html()+': '+subdetailsOptions[$(elm).val()] + '\n';
                }
            } else if(elm == '#billingInvoiceOutcomeForm [name="billingInvoiceSubReasonDetails"]'){
                if (!$("#sub-reason-details-container").hasClass("hidden")) {
                    str += $(elm).parent().siblings('label').html()+': '+$(elm).val() + '\n';
                }
            } else if(elm == '#billingInvoiceOutcomeForm [name="billingInvoiceCategory"]'){
                if (!$("#billingInvoiceOutcomeForm #category-container").hasClass("hidden")) {
                    str += 'Category : '+$(elm).val() + '\n';
                }
            } else {
                str += $(elm).parent().siblings('label').html()+': '+$(elm).val() + '\n';
            }
        @else
            let elementInput = elementName.split('|');
            let elm = '#billingInvoiceOutcomeForm [name="' + elementInput[0] + '"]';
            if(elm == '#billingInvoiceOutcomeForm [name="invoiceNumber"]'){
                str += $(elm).attr('data-label')+': '+$(elm).val() + '\n';
            } else if(elm == '#billingInvoiceOutcomeForm [name="billingInvoiceSubReason"]'){
                if (!$("#sub-reason-container").hasClass("hidden")) {
                    str += $(elm).parent().siblings('label').html()+': '+subdetailsOptions[$(elm).val()] + '\n';
                }
            } else if(elm == '#billingInvoiceOutcomeForm [name="billingInvoiceSubReasonDetails"]'){
                if (!$("#sub-reason-details-container").hasClass("hidden")) {
                    str += $(elm).parent().siblings('label').html()+': '+$(elm).val() + '\n';
                }
            } else {
                str += $(elm).parent().siblings('label').html()+': '+$(elm).val() + '\n';
            }
        @endif
    });
    navigator.clipboard.writeText(str).then(function() {
        console.log('Copied!');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}
$(document).ready(function() {
    $('#billingInvoiceOutcomeModal').on('hidden.bs.modal', function() {
        type = '{{$type}}';
        $('.select-all-' + type).prop('checked', false);
        $('#select-all-checkbox-' + type).prop('checked', false);
        $('#dataTable-' + type + '_wrapper').find('.updateall').remove();
    });
});
$(document).ready(function() {
    const detailsOptions = {!! $jsonDetailsOptions !!};
    $('#billingInvoiceReason').change(function() {
        const reason = $(this).val();
        const subReasonContainer = $('#sub-reason-container');
        const detailsContainer = $('#sub-reason-details-container');
        const subReason = $('#sub-reason');
        const details = $('#sub-reason-details');

        if (reason === 'Cancelled/Voided Claim') {
            subReasonContainer.removeClass('hidden');
            subReason.prop('required', true);
        } else {
            subReason.val('');
            details.val('');
            subReasonContainer.addClass('hidden');
            subReason.prop('required', false);
            detailsContainer.addClass('hidden');
            details.prop('required', false);
        }
        @if(LD::checkFeatureFlag('add-category-option-in-billing-outcome'))
            const categoryContainer = $('#billingInvoiceOutcomeForm #category-container');
            const category = $('#billingInvoiceOutcomeForm #category')
            if(reason == 'Write off request'){
                categoryContainer.removeClass('hidden');
                category.prop('required', true);
            }else{
                category.val('');
                categoryContainer.addClass('hidden');
                category.prop('required', false);
            }
            $('#billingInvoiceOutcomeForm #category').multiselect({includeSelectAllOption: false, enableCaseInsensitiveFiltering: true});
        @endif
    });

    $('#sub-reason').change(function() {
        const subReason = $(this).val();
        const detailsContainer = $('#sub-reason-details-container');
        const details = $('#sub-reason-details');
        details.empty().append('<option value="">Select Details</option>');
        if (subReason === 'move_respite_or_pdn' || subReason === '') {
            details.val('');
            detailsContainer.addClass('hidden');
            details.prop('required', false);
            return;
        }

        $.each(detailsOptions[subReason], function(index, option) {
            details.append($('<option>', {
                value: option,
                text: option
            }));
        });

        detailsContainer.removeClass('hidden');
        details.prop('required', true);
    });
});
</script>
