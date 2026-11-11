<?php
use App\Helpers\LD;
$departments = config('constant.roleDepartment');
$templateCategoryData = (isset($categoryData)) ? $categoryData : null;
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
    <x-slot name="id">template_category</x-slot>
    <x-slot name="title">{{(!empty($templateCategoryData)) ? 'Edit' : 'Add'}} Category</x-slot>
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
            <form method="post" autocomplete="off" id="template_category_form"
            class="scaffold-form" enctype="multipart/form-data">
                @csrf
                <div id="commonfields">
                    <input type="hidden" name="category_id"
                    value = "{{(isset($templateCategoryData)) ? $templateCategoryData->id : ''}}">
                    <div id="candidate_details">
                        <div class="row  pt-2">
                            <div class="col-md-8">
                                <label for="Department">Department</label>
                                <select name="department" id="department" class="form-control">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $department)
                                        <option value="{{$department}}"
                                        {{(isset($templateCategoryData) &&
                                        $templateCategoryData->department == $department) ? 'selected' : ''}}>
                                        {{$department}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row  pt-2">
                            <div class="col-md-8">
                                <label for="Category">Category</label>
                                <input type="text" name="category" id="category"
                                class="form-control"
                                value="{{(isset($templateCategoryData)) ?
                                    $templateCategoryData ->category : ''}}">
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
    $(document).ready(function() {
        $('#template_category_form').validate({
            rules: {
                department: {
                    required: true
                },
                category: {
                    required: true
                },
            },
            messages: {
                department: {
                    required: "Please select a department."
                },
            },
            submitHandler: function(form) {
                $.ajax({
                    url: '{{ route("saveTemplateCategory") }}',
                    type: 'POST',
                    data: $(form).serialize(),
                    success: function(response) {
                        console.log(response.success);
                        if(response.success == 1){
                            $('#template_category').modal('hide');
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
</script>