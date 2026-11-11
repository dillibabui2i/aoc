<x-modal>
    <x-slot name="id">generalInquiryModal</x-slot>
    <x-slot name="size">modal-lg</x-slot>
    <x-slot name="title">How can we help?</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="alert alert-danger" style="display: none" id="errorDiv"></div>
        @if(empty($clinicianData))
            <div class="row g-3">
                <div class="col-md-6">
                    <p>Something went wrong. Please try again later.</p>
                </div>
            </div>
        @else
            <form autocomplete="off" id="general_Inquiry_form" class="scaffold-form" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                <div class="col-md-6">
                    <input type="text" class="form-control" placeholder="First Name" name="FirstName" id="FirstName" value="{{ $clinicianData->FirstName }}" readonly>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" placeholder="Last Name" name="LastName" id="LastName" value="{{ $clinicianData->LastName }}" readonly>
                </div>
                </div>
                <div class="row g-3 pt-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="Mobile" name="Mobile" id="Mobile" value="{{ $clinicianData->Mobile }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="Email" name="External_Email" id="External_Email" value="{{ $clinicianData->External_Email }}" readonly>
                    </div>
                </div>
                <div class="row g-3 pt-2">
                    <div class="col-md-6 pl-2">
                        <div class="d-flex align-items-center">
                            <span class="text-danger me-1" style="font-size: 1.2rem;">*</span>
                                <select class="selectpicker w-100"
                                        id="contact_time"
                                        name="contact_time[]"
                                        data-placeholder="Best time to contact you"
                                        multiple>
                                    <option value="morning">Morning</option>
                                    <option value="afternoon">Afternoon</option>
                                    <option value="evening">Evening</option>
                                </select>
                        </div>
                        <label id="contact_time-error" class="error text-danger" for="contact_time"></label>
                    </div>
                    <div class="col-md-6 pl-2">
                        <div class="d-flex align-items-center">
                            <span class="text-danger me-1" style="font-size: 1.2rem;">*</span>
                                <select class="selectpicker w-100"
                                        id="contact_method"
                                        name="contact_method[]"
                                        data-placeholder="Preferred Contact Method"
                                        data-live-search="true"
                                        multiple>
                                    <option value="call">Call</option>
                                    <option value="email">Email</option>
                                    <option value="text">Text</option>
                                </select>
                        </div>
                        <label id="contact_method-error" class="error text-danger" for="contact_method"></label>
                    </div>
                </div>
                <div class="row g-3 pt-2">
                    <div class="col-md-10 pl-2">
                        <div class="d-flex align-items-start">
                            <span class="text-danger me-1 mt-2">*</span>
                            <textarea placeholder="Message" name="message" id="message" class="form-control"></textarea>
                        </div>
                        <label id="message-error" class="error" for="message"></label>
                    </div>
                </div>
                <div class="text-center mt-4">
                <div id="cover-spin"></div>
                <button onclick="saveNthGeneralInquiry(event, this);" class="btn btn-primary px-4 submit">Send Message</button>
                </div>
            </form>
          @endif
        </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script>
$(document).ready(function () {
    $('.selectpicker').multiselect({
        includeSelectAllOption: true,
    });
});
$(function() {
    $.validator.addMethod("validateMultiSelect", function (value, element) {
            return $(element).find("option:selected").length > 0; // Ensure at least one option is selected
        }, "Opps! This field is required.");
    $("#general_Inquiry_form").validate({
        rules: {
            "contact_time[]": {
                validateMultiSelect: true
            },
            "contact_method[]": {
                validateMultiSelect: true
            },
            "message": {
                required: true
            }
        },
        messages: {
            message: {
                required: "Opps! This field is required."
            },
        },
        errorPlacement: function (error, element) {
            if (element.attr("id") === "contact_method") {
                error.insertAfter(element.parent());
            } else {
                error.insertAfter(element);
            }
        },
        highlight: function (element) {
            if ($(element).hasClass("selectpicker")) {
                $(element).parent().find(".btn-group").addClass("error");
            }
        },
        unhighlight: function (element) {
            if ($(element).hasClass("selectpicker")) {
                $(element).parent().find(".btn-group").removeClass("error");
            }
        }
    });
});
function saveNthGeneralInquiry(e, button){
    e.preventDefault();
    if($('#general_Inquiry_form').valid()){
        button.disabled = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        var data = $('#general_Inquiry_form').serialize();
        data['_token'] = _token;
        $.ajax({
            url: "{{route('nthlanding.saveNthGeneralInquiry')}}",
            type: 'post',
            data: data,
            success: function(data) {
                button.disabled = false;
                if(data.status){
                    $('#generalInquiryModal').modal('hide');
                }else{
                    $('#errorDiv').html(data.message);
                    $('#errorDiv').show();
                }
            }
        });
    }
}
</script>
