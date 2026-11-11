<?php
use App\Helpers\LD;
use App\Helpers\H;

$reason = [
    'Approve' => 'Approve',
    'Reject' => 'Reject',
];
if(!LD::checkFeatureFlag('billing-dashboard-adjustments-make-ops-review-mandatory')) {
    if($vpExists == 1) {
        $reason['Request Ops Review'] = 'Request Ops Review';
    }
}

if (!empty($showRequestOpsReviewOption) && $showRequestOpsReviewOption === true) {
    $reason['Request Ops Review'] = 'Request Ops Review';
}
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
    <x-slot name="id">adjustmentsInvoiceOutcomeModal</x-slot>
    <x-slot name="size">w-40</x-slot>
    <x-slot name="title">Invoice</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 tracker-details">
                <div class="col-md-12 card-cnt p-0 d-flex mb-5">
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Client</h5>
                            <div class="detail-section">
                                <div class="row">
                                    <div class="col-md-6"><b>Client Name</b>: {{$patientsDetails->Patient_FirstName}} {{$patientsDetails->Patient_LastName}}</div>
                                    <div class="col-md-6"><b>Medicaid Number</b>: {{$patientsDetails->MedicaidNumber}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><b>Patient ID</b>: {{$patientsDetails->PatientAccount}}</div>
                                    <div class="col-md-6"><b>SOC</b>: {{H::getFormatedDate($patientsDetails->SOCDate)}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><b>Location</b>: {{$patientsDetails->FacilityName}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><b>DOB</b>: {{H::getFormatedDate($patientsDetails->Patient_DOB)}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><b>Phone</b>: {{!empty($patientsDetails->Patient_MobilPhone)?$patientsDetails->Patient_MobilPhone:$patientsDetails->Patient_Phone}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><b>Payer</b>: {{$patientsDetails->Payer}}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Invoice Details</h5>
                            <div class="detail-section">
                                <p class="card-text">
                                    <b>Invoice #</b>: {{$invoiceDetails->invoice_number}}
                                </p>
                                <p class="card-text">
                                    <b>Statement Period</b>: {{H::getFormatedDate($invoiceDetails->start_date)}} -
                                    {{H::getFormatedDate($invoiceDetails->end_date)}}
                                </p>
                                <p class="card-text">
                                    <b>Invoice Date</b>: {{H::getFormatedDate($invoiceDetails->invoice_date)}}
                                </p>
                                <p class="card-text">
                                    <b>Invoice Amount</b>:
                                    {{ H::getFormatedAmountWithCurrency($invoiceDetails->totalamount) }}
                                </p>
                                <p class="card-text">
                                    <b>Paid Amount</b>:
                                    {{ H::getFormatedAmountWithCurrency($invoiceDetails->paidamount) }}
                                </p>
                                <p class="card-text">
                                    <b>Adjustment Amount</b>:
                                    {{ H::getFormatedAmountWithCurrency($invoiceDetails->other_adjustments +
                                    $invoiceDetails->contractual_adjustments) }}
                                </p>
                                <p class="card-text">
                                    <b>Expected Amount</b>:
                                    {{ H::getFormatedAmountWithCurrency($invoiceDetails->contractual_amount) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row pt-2">
            <div class="col-md-10">
                <form action="" id="adjustmentsInvoiceOutcomeForm" class="form">
                    <input type="hidden" id="selectedId" name="selectedId" value="{{$selectedId}}"/>
                    <input type="hidden" id="invoiceNumber" name="invoiceNumber" data-label="Invoice #" value="{{$invoiceDetails->invoice_number}}"/>
                    @if(($invoiceDetails->adjustment_status == 'Approved' || $allowComplete == 1) && !$showRequestOpsReviewOption)
                    <div class="row col-sm-12">
                        <div class="col-sm-2"></div>
                        <div class="col-sm-8">
                            <input type="checkbox" id="adjustmentsInvoiceComplete" name="adjustmentsInvoiceComplete" class="smscareteam_checkbox" />
                            <label for="adjustmentsInvoiceComplete">Complete Adjustment</label>
                        </div>
                    </div>
                    @else
                    <div class="form-group row d-flex">
                        <label  class="col-sm-2" for="outcome">Outcome</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="adjustmentsOutcome" id="adjustmentsOutcome">
                                <option value="">--- Select One ---</option>
                                @foreach ($reason as $k => $v)
                                    <option value="{{ $v }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if(!$autoRouteOpsReviewFromOutcome)
                        <div class="form-group row hidden" id="requestOpReviewDiv">
                            <label  class="col-sm-2" for="reason">Request Ops Review</label>
                            <div class="col-sm-8">
                                <select class="form-control" name="requestOpsReview" id="requestOpsReview">
                                    <option value="">--- Select One ---</option>
                                    @foreach ($requestOpsUsers as $k => $v)
                                        <option value="{{ $v['id'] }}">{{ $v['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    <div class="form-group row d-flex">
                        <label  class="col-sm-2" for="reason">Comments</label>
                        <div class="col-sm-8">
                            <textarea name="adjustmentsInvoiceComments" id="adjustmentsInvoiceComments" class="form-control"></textarea>
                        </div>
                    </div>
                    @endif
                    <div class="form-group row" id="errorMsgDiv" style="display: none;">
                        <div class="col-md-12 text-center mt-2 error" id="errorMsgBox" style="color: red;">
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-12 text-center mt-2">
                            <button class="btn btn-primary submit" onclick="saveAdjustmentsInvoiceOutcome(event, this);">Save</button>
                            <button class="btn btn-primary" data-dismiss="modal" aria-label="Close">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script>
$(document).ready(function(){
    $("#adjustmentsInvoiceOutcomeForm").validate({
        ignore: [],
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "adjustmentsOutcome":{
                required: true
            },
            'adjustmentsInvoiceComments':{
                required: true
            }
        }
    });
});
function saveAdjustmentsInvoiceOutcome(e, button){
    e.preventDefault();
    if($('#adjustmentsInvoiceOutcomeForm').valid()){
        button.disabled = true;
        $('#errorMsgBox').html('');
        $('#errorMsgDiv').hide();
        let _token = $('meta[name="csrf-token"]').attr('content');
        var data = $('#adjustmentsInvoiceOutcomeForm').serializeObject();
        data['_token'] = _token;
        $.ajax({
            url: "{{route('billing.saveAdjustmentsInvoiceOutcome')}}",
            type: 'post',
            data: data,
            success: function(data) {
                button.disabled = false;
                if(data.status){
                    $('#adjustmentsInvoiceOutcomeModal').modal('hide');
                    $("#dataTable-invoices_adjustments").DataTable().ajax.reload(null, false);
                }else{
                    $('#errorMsgBox').html(data.message);
                    $('#errorMsgDiv').show();
                }
            },
            error: function(xhr) {
                button.disabled = false;
                let message = 'Adjustment failed.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                $('#errorMsgBox').html(message);
                $('#errorMsgDiv').show();
            }
        });
    }
}
@if(empty($autoRouteOpsReviewFromOutcome))
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
@endif
</script>
