<?php
use App\Helpers\LD;
$departments = config('constant.roleDepartment');
$textTemplateData = (isset($textTemplate)) ? $textTemplate : null;
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
    <x-slot name="id">text_template</x-slot>
    <x-slot name="title">{{(!empty($textTemplateData)) ? 'Edit' : 'Add'}} Text Template</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div id="textTemplateSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" data-dismiss="modal" aria-label="Close" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div id="textTemplateFormDiv">
            <form method="post" autocomplete="off" id="text_template_form"
            class="scaffold-form" enctype="multipart/form-data">
                @csrf
                <div id="commonfields">
                    <input type="hidden" name="template_id"
                    value = "{{(isset($textTemplateData)) ? $textTemplateData->id : ''}}">
                    <div id="candidate_details">
                        <div class="row  pt-2">
                            <div class="col-md-8">
                                <label for="Department">Department</label>
                                <select name="department" id="department" class="form-control">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $department)
                                        <option value="{{$department}}"
                                        {{(isset($textTemplateData) &&
                                        $textTemplateData->department == $department) ? 'selected' : ''}}>
                                        {{$department}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row  pt-2">
                            <div class="col-md-8">
                                <label for="Category">Category</label>
                                <select name="category" id="category" class="form-control">
                                    <option value="">Select Category</option>
                                </select>
                            </div>
                        </div>
                        <div class="row  pt-2">
                            <div class="col-md-8">
                                <label for="template_name">Template Name</label>
                                <input type="text" name="template_name"
                                value = "{{(isset($textTemplateData)) ? $textTemplateData->template_name : ''}}"
                                id="template_name" class="form-control">
                            </div>
                        </div>
                        <div class="row  pt-2">
                            <div class="col-md-8">
                                <label for="template">Template Text</label>
                                <textarea name="Template" id="Template" cols="5" rows="5" class="form-control"
                                onkeyup="countChar()"
                                >{{(isset($textTemplateData)) ? $textTemplateData->template : ''}}</textarea>
                            </div>
                            <div class="col-md-8 d-flex textSuccess" id="charCountDiv">
                                <div id="charNum">0</div>&nbsp;/&nbsp;160 characters</div>
                            </div>
                        </div>
                    </div>
                    <div id="candidate_save" class="row pt-2">
                        <div class="col-md-7 text-right">
                            <button type="submit" id="savebtn" class="btn btn-primary">Save</button>
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
    document.getElementById('department').addEventListener('change', function() {
        var departmentName = this.value;
        var url = "{{ route('getTextTemplateCategory', ['departmentName' => ':departmentName']) }}";
        url = url.replace(':departmentName', departmentName);
        let categoryName = '<?php echo isset($textTemplateData) ? $textTemplateData->category : '' ?>';
        // Send AJAX request
        axios.get(url)
            .then(function(response) {
                var categories = response.data;
                var categorySelect = document.getElementById('category');
                // Clear existing options
                categorySelect.innerHTML = '<option value="">Select Category</option>';
                // Populate with new options
                categories.forEach(function(category) {
                    var option = document.createElement('option');
                    option.value = category.category;
                    option.text = category.category;
                    if (category.category == categoryName) {
                        option.selected = true;
                    }
                    categorySelect.appendChild(option);
                });
            })
            .catch(function(error) {
                console.error('Error fetching categories:', error);
            });
    });
    $(document).ready(function() {
        $('#text_template_form').validate({
            rules: {
                department: {
                    required: true
                },
                category: {
                    required: true
                },
                template_name: {
                    required: true
                },
                Template: {
                    required: true
                }
            },
            messages: {
                department: {
                    required: "Please select a department."
                },
                category: {
                    required: "Please select a category."
                },
                template_name: {
                    required: "Please enter the template name."
                },
                Template: {
                    required: "Please enter the template text."
                }
            },
            submitHandler: function(form) {
                $.ajax({
                    url: '{{ route("saveTextTemplate") }}',
                    type: 'POST',
                    data: $(form).serialize(),
                    success: function(response) {
                        console.log(response.success);
                        if(response.success == 1){
                            $('#text_template').modal('hide');
                            $('#dataTable').DataTable().ajax.reload();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            }
        });
    });
    $(document).ready(function(){
        let id = $("input[name='template_id']").val();
        if(id !== ''){
            let departmentElement = document.getElementById('department');
            let changeEvent = new Event('change');
            departmentElement.dispatchEvent(changeEvent);
            countChar();
        }
    });
    function countChar() {
        var maxlimit = 160;
        var len = $('#Template').val().length;
        var charcntr = maxlimit - len;
        $('#charCountDiv').removeClass('textcolorred');
        $('#charCountDiv').addClass('textSuccess');
        if(charcntr < 0) {
            $('#charCountDiv').addClass('textcolorred');
            $('#charCountDiv').removeClass('textSuccess');
        } else {
            charcntr = len;
        }
        $('#charNum').html(charcntr);
    };
</script>