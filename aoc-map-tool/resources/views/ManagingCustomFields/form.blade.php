<?php
    use App\Helpers\H;
    use App\Helpers\LD;
?>
<style>
    label.error{
        margin-bottom: 0px;
    }
</style>
<x-modal>
    <x-slot name="id">customFieldFormModal</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">
        @if($data['mode'] == 'add')
            Add New Custom Field
        @else
            Edit Custom Field
        @endif
    </x-slot>
    <x-slot name="class">add-custom-field-form</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    <form id="addCustomFieldForm" class="addCustomFieldForm">
        <input type="hidden" name="mode" id="mode" value="{{$data['mode']}}"/>
        @if($data['mode'] == 'edit')
            <input type="hidden" name="id" id="id" value="{{$data['id']}}"/>
        @endif
        <div id="errormsg"></div>
        <section class="custom-field-details">
            <div class="form-group row mb-0">
                <div class="col-md-6">
                    <label  class="w-100" for="field_name">Name</label>
                    <input
                        type="text"
                        class="form-control"
                        id="field_name"
                        name="field_name"
                        value="{{($data['mode'] == 'edit') ? $data['field_name'] : ''}}"
                        {{($data['mode'] == 'edit') ? 'disabled' : ''}}/>
                </div>
                <div class="col-md-6">
                    <label for="entity">Entity</label>
                    <select
                        class="form-control"
                        name="entity"
                        id="entity"
                        {{($data['mode'] == 'edit') ? 'disabled' : ''}}>
                        <option value="">-- Select One --</option>
                        @foreach(config('constant')['customFieldEntity'] as $val => $label)
                            @if(!LD::checkFeatureFlag('add-custom-field-to-candidate') && $val === 'candidate')
                                @continue
                            @endif
                            <option
                                {{($data['mode'] == 'edit' && $val == $data['entity']) ? 'selected' : ''}}
                                value="{{$val}}">{{$label[0]}}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="frontend_label">Label</label>
                    <input
                        type="text"
                        class="form-control"
                        id="frontend_label"
                        name="frontend_label"
                        value="{{($data['mode'] == 'edit') ? $data['frontend_label'] : ''}}"/>
                </div>
                <div class="col-md-6">
                    <label for="call_script">Call Script Label</label>
                    <input
                        type="text"
                        class="form-control"
                        id="call_script"
                        name="call_script"
                        value="{{($data['mode'] == 'edit') ? $data['call_script'] : ''}}"/>
                    <div><i>Use [value] to add input field in between the label.</i></div>
                </div>
                <div class="col-md-6">
                    <label for="category">Category</label>
                    <select class="form-control" name="category" id="category">
                        <option value="">-- Select One --</option>
                    </select>
                </div>
                <div class="col-md-6 preferencetagcontainer" style="display:none;">
                    <label for="preference_tag">Preference Tag</label>
                    <input
                        type="text"
                        class="form-control"
                        id="preference_tag"
                        name="preference_tag"
                        value="{{($data['mode'] == 'edit') ? $data['preference_tag'] : ''}}"/>
                </div>
                <div class="col-md-6">
                    <label for="input_type">Input Type</label>
                    <select
                        class="form-control"
                        name="input_type"
                        id="input_type"
                        {{($data['mode'] == 'edit') ? 'disabled' : ''}}>
                        <option value="">-- Select One --</option>
                        @foreach(config('constant')['customFieldType'] as $val => $label)
                        <option
                            {{($data['mode'] == 'edit' && $val == $data['input_type']) ? 'selected' : ''}}
                            value="{{$val}}">{{$label[0]}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="default_value">Default Value</label>
                    <input
                        type="text"
                        class="form-control"
                        id="default_value"
                        name="default_value"
                        value="{{($data['mode'] == 'edit') ? $data['default_value'] : ''}}"/>
                </div>
                <div class="col-md-6 maxchar" style="display:none;">
                    <label  class="w-100" for="max_length">Max Characters</label>
                    <input
                        type="text"
                        class="form-control"
                        id="max_length"
                        name="max_length"
                        value="{{($data['mode'] == 'edit') ? $data['max_length'] : ''}}"/>
                </div>
                <div class="col-md-6 dropdownoptions" style="display:none;">
                    <label  class="w-100" for="options">Options</label>
                    <textarea
                        type="text"
                        class="form-control"
                        id="options"
                        name="options">{{($data['mode'] == 'edit') ? $data['options'] : ''}}</textarea>
                    <p>Enter each option in new line.</p>
                </div>
                <div class="col-md-6">
                    <label for="is_required">Is Required?</label>
                    <select name="is_required" id="is_required" class="form-control">
                        <option value="">--- Select ---</option>
                        <option
                            value="1"
                            {{($data['mode'] == 'edit' && 1 == $data['is_required']) ? 'selected' : ''}}>Yes</option>
                        <option
                            value="0"
                            {{($data['mode'] == 'edit' && 0 == $data['is_required']) ? 'selected' : ''}}>No</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="state">Frontend Instruction</label>
                    <textarea
                        name="frontend_instruction"
                        id="frontend_instruction"
                        class="form-control"
                    >{{($data['mode'] == 'edit') ? $data['frontend_instruction'] : ''}}</textarea>
                </div>
                <div class="col-md-6">
                    <label for="is_dependent">Is Dependent?</label>
                    <select name="is_dependent" id="is_dependent" class="form-control">
                        <option value="">--- Select ---</option>
                        <option
                            value="1"
                            {{($data['mode'] == 'edit' && 1 == $data['is_dependent']) ? 'selected' : ''}}>Yes</option>
                        <option
                            value="0"
                            {{($data['mode'] == 'edit' && 0 == $data['is_dependent']) ? 'selected' : ''}}>No</option>
                    </select>
                </div>
                <div class="col-md-6 dependent_on_container" style="display:none;">
                    <label  class="w-100" for="max_length">Dependent On</label>
                    <div class="row pl-3 pr-3">
                        <div class="col-md-6 d-inline p-0">
                            <select
                                name="dependent_on_field"
                                id="dependent_on_field"
                                class="form-control">
                                <option value="" data-category="">--- Select dependented field ---</option>
                                @foreach($data['dependentFields'] as $dependentField)
                                <option
                                    value="{{$dependentField['id']}}"
                                    data-category="{{$dependentField['category']}}">{{$dependentField['field_name']}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5 d-inline ml-4 p-0" style="margin-left:1.8rem!important">
                            <select
                                name="dependent_on_value"
                                id="dependent_on_value"
                                class="form-control">
                                <option value="">--- Select dependented value ---</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label  class="w-100" for="sort">Sort</label>
                    <input
                        type="number"
                        class="form-control"
                        id="sort"
                        name="sort"
                        value="{{($data['mode'] == 'edit') ? $data['sort'] : ''}}"/>
                </div>
                @if($data['mode'] == 'edit')
                    <div class="col-md-12">
                        <label for="activity_log" class="text-left">Activity Log</label>
                        @php
                            $logData = H::getHelper("ActivityNotes")->getActivityNotes(['entity' => 'custom-field-change', 'entity_id' => $data['id']]);
                        @endphp
                        <div style="width: 100%; overflow-y: scroll; height: 225px;">
                        @if(count($logData) > 0)
                            <table style="width: 100%;border: 1px solid #dee2e6;" class="table table-striped" summary="User Form" aria-hidden="true">
                                @foreach ($logData->toArray() as $data2)
                                    <tr>
                                        <td class="text-left">{!!$data2['notes']!!}</td>
                                        <td class="text-left">
                                            {{
                                                date("M d Y", strtotime($data2['created_at'])) .
                                                ' at ' . date("g:i A", strtotime($data2['created_at']))
                                            }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                        </div>
                    </div>
                @endif
            </div>
        </section>
        <div class="form-group row mt-4">
            <div class="col-md-12 text-center">
                <button class="btn btn-primary" onclick="saveCustomField(event, this);">Save</button>
            </div>
        </div>
    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script>
    var customFieldCategory = JSON.parse('{!! json_encode(config('constant')['customFieldCategory']) !!}');
    var dependableFieldsJson = JSON.parse('{!! H::formSafeJson(json_encode($data['dependentFields'])) !!}');
    function findElement(id) {
        result = -1;
        for( var i = 0, len = dependableFieldsJson.length; i < len; i++ ) {
            if( dependableFieldsJson[i]['id'] == id ) {
                result = dependableFieldsJson[i];
                break;
            }
        }
        return result;
    }
    function saveCustomField(e, button){
        e.preventDefault();
        $('#errormsg').empty();
        if($('#addCustomFieldForm').valid()){
            button.disabled = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#addCustomFieldForm').serializeObject();
            data['_token'] = _token;
            $.ajax({
                url: "{{route('manageCustomFields.save')}}",
                type: 'post',
                data: data,
                success: function(data) {
                    button.disabled = false;
                    $('#errormsg').empty().html(data.msg);
                    if(data.status) {
                        $('#customFieldFormModal').modal('hide');
                        $('#dataTable').DataTable().ajax.reload();
                    }
                }
            });
        }
    }
    $("#addCustomFieldForm").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "field_name":{
                required: true,
            },
            "entity":{
                required: true,
            },
            "input_type":{
                required: true,
            },
            "frontend_label":{
                required:true
            },
            "is_required":{
                required:true
            },
            "max_length":{
                digits: true
            },
            "is_dependent": {
                required:true
            },
            "dependent_on_field": {
                required:{
                    depends: function(element) {
                        if ($('#is_dependent').val() == 1) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
            },
            "dependent_on_value": {
                required:{
                    depends: function(element) {
                        if ($('#is_dependent').val() == 1) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
            }
        },
        //errorPlacement: function(error, element) {}
    });
    $('#entity').change(function(event, data) {
        let curVal = $(this).val();
        $('#category').empty();
        $('#category').append('<option value="">-- Select One --</option>');
        if (customFieldCategory[curVal] != undefined) {
            $.each(customFieldCategory[curVal], function(i, item) {
                $('#category').append(
                    '<option '+((data != undefined && data == customFieldCategory[curVal][i]) ? 'selected' : '')
                    +' value="'+customFieldCategory[curVal][i]+'">'+
                    customFieldCategory[curVal][i]+'</option>'
                );
            });
            $("#category").rules("add", {
                required: true
            });
        } else {
            $("#category").rules("remove", 'required');
        }
    });
    $('#entity').trigger('change',"{{($data['mode'] == 'edit') ? $data['category'] : ''}}");
    $('#category').change(function(event, data) {
        let curVal = $(this).val();
        if (curVal == 'Preferences' || data == 'Preferences') {
            $('.preferencetagcontainer').show();
            $('#input_type').val('boolean');
        } else {
            $('.preferencetagcontainer').hide();
        }
        if (data == undefined) {
            $('#is_dependent').val('');
            $('.dependent_on_container').hide();
            $('#dependent_on_field').val('');
            $('#dependent_on_value').val('');
        }
    });
    $('#category').trigger('change',"{{($data['mode'] == 'edit') ? $data['category'] : ''}}");
    $('#input_type').change(function() {
        if ($(this).val() == 'textarea') {
            $('.maxchar').show();
        } else {
            $('.maxchar').hide();
        }
        if ($(this).val() == 'dropdown' || $(this).val() == 'mdropdown') {
            $('.dropdownoptions').show();
        } else {
            $('.dropdownoptions').hide();
        }
    });
    $('#input_type').trigger('change');
    $('#is_dependent').change(function() {
        if ($(this).val() == 1) {
            $('.dependent_on_container').show();
            $.each($('#dependent_on_field option'), function(key, value) {   
                if ($(this).data('category') == $('#category').val()) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        } else {
            $('.dependent_on_container').hide();
            $('#dependent_on_field').val('');
            $('#dependent_on_value').val('');
        }
    });
    $('#is_dependent').trigger('change');

    $('#dependent_on_field').change(function(event, data) {
        if (data != '' && data != undefined) {
            data = JSON.parse(data);
            if (data['dependent_on_field'] != undefined) {
                $('#dependent_on_field').val(data['dependent_on_field']);
            }
        }
        let ele = findElement($('#dependent_on_field').val(), $('#category').val());
        if (ele != -1) {
            $('#dependent_on_value').empty();
            $('#dependent_on_value')
                    .append($("<option></option>")
                    .attr("value", '')
                    .text('--- Select dependented value ---'));
            if (ele['input_type'] == 'boolean') {
                ele['options'] = 'Yes\r\nNo';
            }
            $.each(ele['options'].split('\r\n'), function(key, value) {   
                $('#dependent_on_value')
                    .append($("<option></option>")
                    .attr("value", value)
                    .text(value));
            });
        }
        if (data != '' && data != undefined) {
            if (data['dependent_on_field'] != undefined) {
                $('#dependent_on_value').val(data['dependent_on_value']);
            }
        }
    });
    $('#dependent_on_field').trigger(
        'change',
        '{!! ($data['mode'] == 'edit') ? $data['dependent_on'] : '{}' !!}'
    );
</script>
