<?php
use App\Helpers\LD;
?>
<style>
div.custom-control-right {
    padding-right: 24px;
    padding-left: 0;
    margin-left: 0;
    margin-right: 0;
}

div.custom-control-right .custom-control-label::after {
    right: -1.5rem;
    left: auto;
}

div.custom-control-right .custom-control-label::before {
    right: -2.35rem;
    left: auto;
}

.custom-control-right .custom-control-label {
    line-height: 22px;
}

.schedule-appointment {
    margin-top: 40px;
}

.schedule-appointment fieldset {
    padding: 15px;
    border: 1px solid #ddd;
}

#ui-datepicker-div {
    position: absolute !important;
    left: auto !important;
    top: auto !important;
    box-shadow: 0px 0px 3px 0px;
}

.error {
    color: red !important;
}
</style>
<x-modal>
    <x-slot name="id">enhancement_suggestion</x-slot>
    <x-slot name="title">{{ $myTitle }}</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div id="enhancementSuggestionSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" data-dismiss="modal" aria-label="Close" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div id="enhancementSuggestionFormDiv">
            <form method="post" autocomplete="off" id="enhancement_suggestion_form" class="scaffold-form"
                enctype="multipart/form-data">
                @csrf
                <div id="commonfields">

                    <div id="candidate_details">
                        <div class="row  pt-2">
                            <div class="col-md-8">
                                <label for="city">Name</label>
                                <input type="input" class="form-control" id="name" name="name"
                                    value="{{ auth()->user()->name}}" readonly>
                            </div>

                        </div>
                        <div class="row  pt-2">
                            <div class="col-md-8 ui-widget">
                                <label for="city">Subject</label>
                                <input type="input" class="form-control" id="subject" aria-describedby="emailHelp"
                                    name="subject" maxlength="200">
                            </div>

                        </div>

                        <div class="row pt-2">
                            <div class="col-md-8">
                                <label for="explanation_detail">Explanation or details about your suggestion</label>
                                <textarea class="form-control" id="explanation_detail" name="explanation_detail"
                                    maxlength="1000"></textarea>
                            </div>
                        </div>
                        @if(LD::checkFeatureFlag('add-upload-buttons-for-attachment-on-ticket-form'))
                        <div class="row pt-2">
                            <div class="col-md-8">
                                <label for="screenshot">Upload screenshot </label>
                                <div id="jobMain">
                                    <input type="file" class="form-control" id="screenshot" name="screenshot[]">
                                </div>
                            </div>
                            <div class="col-md-4 pt-4" id="addMoreJobDiv">
                                    <a href="javascript:void(0)" id="addMoreJobLink">Add More Upload Button</a>
                            </div>
                        </div>
                        <div class="row pt-2">
                            <div class="col-md-8">
                                <label class="pt-1">(Allowed Up to 2MB; Allowed type: .jpeg, .jpg, .png, .pdf) </label>
                            </div>
                        </div>
                        @else
                        <div class="row pt-2">
                            <div class="col-md-8">
                                <label for="screenshot">Upload screenshot </label>
                                <input type="file" class="form-control" id="screenshot" name="screenshot[]">
                                <label class="pt-2">(Allowed Up to 2MB; Allowed type: .jpeg, .jpg, .png, .pdf) </label>
                            </div>
                        </div>
                        @endif
                        <div class="row pt-2">
                            <div class="col-md-8">
                                <span class="text-danger" id="screenshotError"></span>
                            </div>
                        </div>
                    </div>
                    <div id="candidate_save" class="row pt-2">
                        <div class="col-md-7 text-right">
                            <button type="submit" id="savebtn" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>

<script type="text/javascript">
    
    // Wait for the DOM to be ready
    $(function() {
        const imageUploadAjax = (blobInfo, progress) => new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            $.ajax({
                url: "{{route('emailtemplate.uploadfiles')}}",
                method: "POST",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(data) {
                    const json = JSON.parse(data);
                    resolve(json.location);
                },
                error: function(data) {
                    reject("Error Uploading Image");
                },
            });

        });
        if (tinymce.get('explanation_detail') !== null) {
            tinymce.get('explanation_detail').remove();
        }
        @if(LD::checkFeatureFlag('add-upload-buttons-for-attachment-on-ticket-form'))
        tinymce.init({
            selector: 'textarea#explanation_detail',
            plugins: 'image code',
            toolbar: 'undo redo bold italic underline alignleft aligncenter alignright alignjustify image code',
            height: 400,
            relative_urls: false,
            remove_script_host: false,
            convert_urls: true,
            paste_data_images: false,

        });
        @else
        tinymce.init({
            selector: 'textarea#explanation_detail',
            plugins: 'image code',
            toolbar: 'undo redo bold italic underline alignleft aligncenter alignright alignjustify image code',
            height: 400,
            relative_urls: false,
            remove_script_host: false,
            convert_urls: true,

            images_upload_handler: imageUploadAjax,

        });
        @endif
        $("#enhancement_suggestion_form").validate({
            onfocusout: false,
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
                "explanation_detail": {
                    required: true
                },
                "subject": {
                    required: true
                }

            }
        });
    });
    var jobDivCount = 2;
    $(document).ready(function() {
        $("#addMoreJobLink").on('click', function(){
            var selectOptionHtml = $("#category1").html();
            var newHtml = '<div class="pt-2" id="jobDiv'+jobDivCount+'">\
                <div class="row pt-2"><div class="col-md-8">\
                <input type="file" class="form-control" id="screenshot" name="screenshot[]">\
            </div><div class="col-md-4">\
            <a href="javascript:void(0)" class="removeMoreJobLink" \
                        onclick=removeMoreDiv("'+jobDivCount+'")>Remove</a></div></div>\
                    </div></div></div>';
            $('#jobMain').append(newHtml);
            jobDivCount++;
            if(jobDivCount > 5) {
                $('#addMoreJobDiv').hide(); return false;
            }else {
                $('#addMoreJobDiv').show();
            }
        });
    });
    function removeMoreDiv(div_id) {
        $('#jobDiv'+div_id).remove();
        jobDivCount--;
        if(jobDivCount > 5) {
            $('#addMoreJobDiv').hide(); return false;
        }else {
            $('#addMoreJobDiv').show();
        }
    }
    $("form#enhancement_suggestion_form").submit(function(e) {
        e.preventDefault();
        tinyMCE.triggerSave(); //this line of code will use to update textarea content

        //alert("In Progress");
        //return false;
        if ($("#enhancement_suggestion_form").valid() == false) {
            return false;
        }
        //$("#form#enhancement_suggestion_form")
        var formData = new FormData(this);
        $('#savebtn').attr("disabled", true);
        $('#savebtn').text('Please wait...');
        $.ajax({
            url: "{{ route('postEnhancementSuggestion') }}",
            type: "POST",
            data: formData,
            cache: false,
            contentType: false,
            processData: false, //only input
            success: function(response) {

                $('#savebtn').removeAttr("disabled");
                $('#savebtn').text('Save');
                if (response) {

                    if (response.success == 1) {
                        $("#successBody").html(response.message);
                        $("#enhancementSuggestionFormDiv").hide();
                        $("#enhancementSuggestionSuccessDiv").show();
                        //$("#scheduleNextForm").modal('show');
                        $("#enhancement_suggestion_form")[0].reset();
                    } else {
                        $("#enhancementSuggestionFormDiv").show();
                        $("#enhancementSuggestionSuccessDiv").hide();
                        if (response.success == 0) {
                            $('#screenshotError').text(response.error[0]);
                            console.log(response.error[0]);
                            return false;
                        }
                    }
                }
            },
            error: function(response) {
                $('#screenshotError').text(response.error[0]);
                console.log(response.error[0]);
                return false;
            }
        });
    });
</script>
