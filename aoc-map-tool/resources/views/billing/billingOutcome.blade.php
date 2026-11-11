<?php
use App\Helpers\LD;
if(LD::checkFeatureFlag('change-outcome-reasons-unbilled-schedules-billing-dashboard')){
    $outcomes = config('constant.unbilled_reason_subreason_list');
    $outcome=$outcomes[$type];
}
?>
<x-modal>
    <x-slot name="id">billingOutcomeModal</x-slot>
    <x-slot name="size">w-40</x-slot>
    <x-slot name="title">Billing</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <form action="" id="billingOutcomeForm" class="form">
            <input type="hidden" id="selectedIds" name="selectedIds" value="{{$selectedIds}}"/>
            <input type="hidden" id="bucket_type" name="bucket_type" value="{{$type}}"/>
            <div class="form-group row d-flex">
                <label  class="col-sm-2" for="reason">Reason</label>
                <div class="col-sm-8">
                    <select class="form-control" name="billingReason" id="billingReason">
                        <option value="">--- Select One ---</option>
                        @foreach ($outcome as $k => $v)
                            @if (is_array($v))
                                <option value="{{ $k }}">{{ $k }}</option>
                            @else
                                <option value="{{ $v }}">{{ $v }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group row d-flex hide billingSubReason">
                <label  class="col-sm-2" for="reason">Sub Reason</label>
                <div class="col-sm-8">
                    <select class="form-control" name="billingSubReason" id="billingSubReason">
                    </select>
                </div>
            </div>
            <div class="form-group row d-flex">
                <label  class="col-sm-2" for="reason">Comments</label>
                <div class="col-sm-8">
                    <textarea name="billingComments" id="billingComments" class="form-control"></textarea>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-12 text-center mt-2">
                    <a class="copy-form-btn" onclick="copyFormToClipboard();">Copy Text from Form</a>
                    <button class="btn btn-primary submit" onclick="saveBillingOutcome(event, this);">Save</button>
                    <button class="btn btn-primary" data-dismiss="modal" aria-label="Close">Cancel</button>
                </div>
            </div>
        </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script>
    var reasons = JSON.parse('<?php echo json_encode($outcome); ?>');
    $('#billingReason').change(function() {
        var selectedReason = $(this).val();
        if(typeof reasons[selectedReason] !== 'undefined' && reasons[selectedReason].length > 0){
            var subReasonHtml = '<option value="">--- Select One ---</option>';
            $.each(reasons[selectedReason], function(index, reason){
                console.log(reason,'reason: ' );
                subReasonHtml += '<option value="'+reason+'">'+reason+'</option>';
            });
            console.log(subReasonHtml);
            $('.billingSubReason').removeClass('hide');
            $('#billingSubReason').html(subReasonHtml);
        }else{
            var subReasonHtml = '<option value="" selected>--- Select One ---</option>';
            $('#billingSubReason').html(subReasonHtml);
            $('.billingSubReason').addClass('hide');
        }
    });

    $(document).ready(function(){
        $("#billingOutcomeForm").validate({
            ignore: [],
            onfocusout: false,
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
                "billingReason":{
                    required: true
                },
                "billingSubReason": {
                    required: function(element) {
                        return !($('.billingSubReason').hasClass('hide'));
                    }
                },
                'billingComments':{
                    required: true
                }
            }
        });
    });
    function saveBillingOutcome(e, button){
        e.preventDefault();
        if($('#billingOutcomeForm').valid()){
            $('.disabled').removeAttr('disabled');
            button.disabled = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#billingOutcomeForm').serializeObject();
            data['_token'] = _token;
            $.ajax({
                url: "{{route('billing.saveBillingOutcome')}}",
                type: 'post',
                data: data,
                success: function(data) {
                    button.disabled = false;
                    if(data.status){
                        $('#billingOutcomeModal').modal('hide');
                        $('.dataTable').DataTable().ajax.reload(null, false);
                        showHideUpdateButtonFor{{$type}}(false);
                    }else{
                        console.log('Error:' + data.msg);
                    }
                    $('.disabled').attr('disabled','disabled');
                }
            });
        }
    }
    function copyFormToClipboard() {
        var str = '';
        let elements = [
            'billingReason|select',
            'billingSubReason|select',
            'billingComments|text',
        ]
        $.each ( elements, function (index, elementName) {
            let elementInput = elementName.split('|');
            let elm = '#billingOutcomeForm [name="' + elementInput[0] + '"]';
            str += $(elm).parent().siblings('label').html()+': '+$(elm).val() + '\n';
        });
        navigator.clipboard.writeText(str).then(function() {
            console.log('Copied!');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
    $(document).ready(function() {
       $('#billingOutcomeModal').on('hidden.bs.modal', function() {
            type = '{{$type}}';
            $('.select-all-' + type).prop('checked', false);
            $('#select-all-checkbox-' + type).prop('checked', false);
            $('#dataTable-' + type + '_wrapper').find('.updateall').remove();
        });
    });
</script>
