<?php
	use App\Helpers\H;
    $customFieldEntity = config('constant')['customFieldEntity'];
    $table = $customFieldEntity[$field['entity']][1];
    $label = ($labelField??'frontend_label' == 'call_script') ? 'call_script' : 'frontend_label';
    $pos = strpos($field[$label], "[value]");
    $depedent = $field['is_dependent'];
    $dependOn = json_decode($field['dependent_on'], true);
?>
<style>
    .customfield label {
        font-weight: bold;
    }
	.datepicker-dropdown {
		z-index: 9999 !important;
	}
</style>
@if($field['input_type'] == 'text')
    @if ($label == 'call_script' && $pos !== false)
        <div id="customfield{{$field['id']}}" class="customfield {{$depedent == 1 ? 'hide' : ''}}">
            <label for="{{$field['db_field_name']}}">
                {{substr($field['call_script'], 0, $pos)}}
                <span
                class="custom-field-editable"
                data-emptytext='Click to edit'
                data-id='{{$field['id']}}'
                data-dependent='{{$field['is_dependent']}}'
                data-dependentOn='{!! $field['dependent_on'] !!}'
                data-type='text' data-url='{{route("editable")}}'
                data-pk="{{$id}}" data-name='{{$field['db_field_name']}}'
                data-category='{{$field['category']}}'
                data-table='{{$table}}'
                data-label='{{$field['frontend_label']}}'
                data-customfield_type='{{$field['input_type']}}'
                @if(isset($details->{$field['db_field_name']}) && $details->{$field['db_field_name']} != '')
                data-value='{{ $details->{$field['db_field_name']} }}'
                @endif
                @if(!empty($field['preference_tag']))
                data-preference_tag='{{ $field['preference_tag'] }}'
                @endif
                data-required='{{($field['is_required']) ? 1 : 0}}'></span>
                {{substr($field['call_script'], ($pos + 7))}}
            </label>
            @if(!empty($field['frontend_instruction']))
                <a
                    href="javascript:void(0)"
                    class='popover-dismiss'
                    title="Help"
                    data-content="{!! $field['frontend_instruction'] !!}">
                        <i class="fa fa-question-circle" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    @else
        <div id="customfield{{$field['id']}}" class="customfield {{$depedent == 1 ? 'hide' : ''}}">
            <label for="{{$field['db_field_name']}}">{{$field['frontend_label']}} : </label>
            <span
                class="custom-field-editable"
                data-emptytext='Click to edit'
                data-id='{{$field['id']}}'
                data-dependent='{{$field['is_dependent']}}'
                data-dependentOn='{!! $field['dependent_on'] !!}'
                data-type='text' data-url='{{route("editable")}}'
                data-pk="{{$id}}" data-name='{{$field['db_field_name']}}'
                data-category='{{$field['category']}}'
                data-table='{{$table}}'
                data-label='{{$field['frontend_label']}}'
                data-customfield_type='{{$field['input_type']}}'
                @if(isset($details->{$field['db_field_name']}) && $details->{$field['db_field_name']} != '')
                data-value='{{ $details->{$field['db_field_name']} }}'
                @endif
                @if(!empty($field['preference_tag']))
                data-preference_tag='{{ $field['preference_tag'] }}'
                @endif
                data-required='{{($field['is_required']) ? 1 : 0}}'></span>
            @if(!empty($field['frontend_instruction']))
                <a
                    href="javascript:void(0)"
                    class='popover-dismiss'
                    title="Help"
                    data-content="{!! $field['frontend_instruction'] !!}">
                        <i class="fa fa-question-circle" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    @endif
@elseif($field['input_type'] == 'textarea')
    @if ($label == 'call_script' && $pos !== false)
        <div id="customfield{{$field['id']}}" class="customfield {{$depedent == 1 ? 'hide' : ''}}">
            <label for="{{$field['db_field_name']}}">
                {{substr($field['call_script'], 0, $pos)}}
                <span
                class="custom-field-editable"
                data-emptytext='Click to edit'
                data-id='{{$field['id']}}'
                data-dependent='{{$field['is_dependent']}}'
                data-dependentOn='{!! $field['dependent_on'] !!}'
                data-type='textarea' data-url='{{route("editable")}}'
                data-pk="{{$id}}" data-name='{{$field['db_field_name']}}'
                data-category='{{$field['category']}}'
                data-table='{{$table}}'
                data-label='{{$field['frontend_label']}}'
                data-customfield_type='{{$field['input_type']}}'
                @if(isset($details->{$field['db_field_name']}) && $details->{$field['db_field_name']} != '')
                data-value='{{ $details->{$field['db_field_name']} }}'
                @endif
                @if(!empty($field['preference_tag']))
                data-preference_tag='{{ $field['preference_tag'] }}'
                @endif
                data-required='{{($field['is_required']) ? 1 : 0}}'></span>
                {{substr($field['call_script'], ($pos + 7))}}
            </label>
            @if(!empty($field['frontend_instruction']))
                <a
                    href="javascript:void(0)"
                    class='popover-dismiss'
                    title="Help"
                    data-content="{!! $field['frontend_instruction'] !!}">
                        <i class="fa fa-question-circle" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    @else
        <div id="customfield{{$field['id']}}" class="customfield {{$depedent == 1 ? 'hide' : ''}}">
            <label for="{{$field['db_field_name']}}">{{$field['frontend_label']}} : </label>
            <span
                class="custom-field-editable"
                data-emptytext='Click to edit'
                data-id='{{$field['id']}}'
                data-dependent='{{$field['is_dependent']}}'
                data-dependentOn='{!! $field['dependent_on'] !!}'
                data-type='textarea' data-url='{{route("editable")}}'
                data-pk="{{$id}}" data-name='{{$field['db_field_name']}}'
                data-category='{{$field['category']}}'
                data-table='{{$table}}'
                data-label='{{$field['frontend_label']}}'
                data-customfield_type='{{$field['input_type']}}'
                @if(isset($details->{$field['db_field_name']}) && $details->{$field['db_field_name']} != '')
                data-value='{{ $details->{$field['db_field_name']} }}'
                @endif
                @if(!empty($field['preference_tag']))
                data-preference_tag='{{ $field['preference_tag'] }}'
                @endif
                data-required='{{($field['is_required']) ? 1 : 0}}'></span>
            @if(!empty($field['frontend_instruction']))
                <a
                    href="javascript:void(0)"
                    class='popover-dismiss'
                    title="Help"
                    data-content="{!! $field['frontend_instruction'] !!}">
                        <i class="fa fa-question-circle" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    @endif
@elseif($field['input_type'] == 'date')
    @if ($label == 'call_script' && $pos !== false)
        <div id="customfield{{$field['id']}}" class="customfield {{$depedent == 1 ? 'hide' : ''}}">
            <label for="{{$field['db_field_name']}}">
                {{substr($field['call_script'], 0, $pos)}}
                <span
                class="custom-field-editable"
                data-emptytext='Click to edit'
                data-id='{{$field['id']}}'
                data-dependent='{{$field['is_dependent']}}'
                data-dependentOn='{!! $field['dependent_on'] !!}'
                data-type='date' data-url='{{route("editable")}}'
                data-pk="{{$id}}" data-name='{{$field['db_field_name']}}'
                data-category='{{$field['category']}}'
                data-table='{{$table}}'
                data-viewformat='mm/dd/yyyy'
                data-display=''
                data-label='{{$field['frontend_label']}}'
                data-customfield_type='{{$field['input_type']}}'
                @if(isset($details->{$field['db_field_name']}) && $details->{$field['db_field_name']} != '')
                data-value='{{ $details->{$field['db_field_name']} }}'
                @endif
                @if(!empty($field['preference_tag']))
                data-preference_tag='{{ $field['preference_tag'] }}'
                @endif
                data-required='{{($field['is_required']) ? 1 : 0}}'>
					{{ H::getFormatedDate($details->{$field['db_field_name']}) }}
				</span>
                {{substr($field['call_script'], ($pos + 7))}}
            </label>
            @if(!empty($field['frontend_instruction']))
                <a
                    href="javascript:void(0)"
                    class='popover-dismiss'
                    title="Help"
                    data-content="{!! $field['frontend_instruction'] !!}">
                        <i class="fa fa-question-circle" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    @else
        <div id="customfield{{$field['id']}}" class="customfield {{$depedent == 1 ? 'hide' : ''}}">
            <label for="{{$field['db_field_name']}}">{{$field['frontend_label']}} : </label>
            <span
                class="custom-field-editable"
                data-emptytext='Click to edit'
                data-id='{{$field['id']}}'
                data-dependent='{{$field['is_dependent']}}'
                data-dependentOn='{!! $field['dependent_on'] !!}'
                data-type='date' data-url='{{route("editable")}}'
                data-pk="{{$id}}" data-name='{{$field['db_field_name']}}'
                data-category='{{$field['category']}}'
                data-table='{{$table}}'
                data-viewformat='mm/dd/yyyy'
                data-display=''
                data-label='{{$field['frontend_label']}}'
                data-customfield_type='{{$field['input_type']}}'
                @if(isset($details->{$field['db_field_name']}) && $details->{$field['db_field_name']} != '')
                data-value='{{ $details->{$field['db_field_name']} }}'
                @endif
                @if(!empty($field['preference_tag']))
                data-preference_tag='{{ $field['preference_tag'] }}'
                @endif
                data-required='{{($field['is_required']) ? 1 : 0}}'>
				{{ H::getFormatedDate($details->{$field['db_field_name']}) }}
				</span>
            @if(!empty($field['frontend_instruction']))
                <a
                    href="javascript:void(0)"
                    class='popover-dismiss'
                    title="Help"
                    data-content="{!! $field['frontend_instruction'] !!}">
                        <i class="fa fa-question-circle" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    @endif
@elseif($field['input_type'] == 'dropdown')
@php
    $source = [];
    foreach(preg_split('/\r\n|[\r\n]/', $field['options']) as $option) {
        $source[$option]  = $option;
    }
@endphp
    @if ($label == 'call_script' && $pos !== false)
        <div id="customfield{{$field['id']}}" class="customfield {{$depedent == 1 ? 'hide' : ''}}">
            <label for="{{$field['db_field_name']}}">
                {{substr($field['call_script'], 0, $pos)}}
                <span
                    class="custom-field-editable"
                    data-emptytext='Click to edit'
                    data-id='{{$field['id']}}'
                    data-dependent='{{$field['is_dependent']}}'
                    data-dependentOn='{!! $field['dependent_on'] !!}'
                    data-type='select' data-url='{{route("editable")}}'
                    data-pk="{{$id}}" data-name='{{$field['db_field_name']}}'
                    data-category='{{$field['category']}}'
                    data-table='{{$table}}'
                    data-source='{{json_encode($source)}}'
                    data-label='{{$field['frontend_label']}}'
                    data-customfield_type='{{$field['input_type']}}'
                    @if(isset($details->{$field['db_field_name']}) && $details->{$field['db_field_name']} != '')
                    data-value='{{ $details->{$field['db_field_name']} }}'
                    @endif
                    @if(!empty($field['preference_tag']))
                    data-preference_tag='{{ $field['preference_tag'] }}'
                    @endif
                    data-required='{{($field['is_required']) ? 1 : 0}}'
                    data-prepend=' Please Select '></span>
                {{substr($field['call_script'], ($pos + 7))}}
            </label>
            @if(!empty($field['frontend_instruction']))
                <a
                    href="javascript:void(0)"
                    class='popover-dismiss'
                    title="Help"
                    data-content="{!! $field['frontend_instruction'] !!}">
                        <i class="fa fa-question-circle" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    @else
        <div id="customfield{{$field['id']}}" class="customfield {{$depedent == 1 ? 'hide' : ''}}">
            <label for="{{$field['db_field_name']}}">{{$field['frontend_label']}} : </label>
            <span
                class="custom-field-editable"
                data-emptytext='Click to edit'
                data-id='{{$field['id']}}'
                data-dependent='{{$field['is_dependent']}}'
                data-dependentOn='{!! $field['dependent_on'] !!}'
                data-type='select' data-url='{{route("editable")}}'
                data-pk="{{$id}}" data-name='{{$field['db_field_name']}}'
                data-category='{{$field['category']}}'
                data-table='{{$table}}'
                data-source='{{json_encode($source)}}'
                data-label='{{$field['frontend_label']}}'
                data-customfield_type='{{$field['input_type']}}'
                @if(isset($details->{$field['db_field_name']}) && $details->{$field['db_field_name']} != '')
                data-value='{{ $details->{$field['db_field_name']} }}'
                @endif
                @if(!empty($field['preference_tag']))
                data-preference_tag='{{ $field['preference_tag'] }}'
                @endif
                data-required='{{($field['is_required']) ? 1 : 0}}'
                data-prepend=' Please Select '></span>
            @if(!empty($field['frontend_instruction']))
                <a
                    href="javascript:void(0)"
                    class='popover-dismiss'
                    title="Help"
                    data-content="{!! $field['frontend_instruction'] !!}">
                        <i class="fa fa-question-circle" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    @endif
@elseif($field['input_type'] == 'boolean')
@php
    $source = [];
    $source['Yes'] = 'Yes';
    $source['No'] = 'No';
@endphp
    @if ($label == 'call_script' && $pos !== false)
        <div id="customfield{{$field['id']}}" class="customfield {{$depedent == 1 ? 'hide' : ''}}">
            <label for="{{$field['db_field_name']}}">
                {{substr($field['call_script'], 0, $pos)}}
                <span
                    class="custom-field-editable"
                    data-emptytext='Click to edit'
                    data-id='{{$field['id']}}'
                    data-dependent='{{$field['is_dependent']}}'
                    data-dependentOn='{!! $field['dependent_on'] !!}'
                    data-type='select' data-url='{{route("editable")}}'
                    data-pk="{{$id}}" data-name='{{$field['db_field_name']}}'
                    data-category='{{$field['category']}}'
                    data-table='{{$table}}'
                    data-source='{{json_encode($source)}}'
                    data-label='{{$field['frontend_label']}}'
                    data-customfield_type='{{$field['input_type']}}'
                    @if(isset($details->{$field['db_field_name']}) && $details->{$field['db_field_name']} != '')
                    data-value='{{ $details->{$field['db_field_name']} }}'
                    @endif
                    @if(!empty($field['preference_tag']))
                    data-preference_tag='{{ $field['preference_tag'] }}'
                    @endif
                    data-required='{{($field['is_required']) ? 1 : 0}}'></span>
                {{substr($field['call_script'], ($pos + 7))}}
            </label>
            @if(!empty($field['frontend_instruction']))
                <a
                    href="javascript:void(0)"
                    class='popover-dismiss'
                    title="Help"
                    data-content="{!! $field['frontend_instruction'] !!}">
                        <i class="fa fa-question-circle" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    @else
        <div id="customfield{{$field['id']}}" class="customfield {{$depedent == 1 ? 'hide' : ''}}">
            <label for="{{$field['db_field_name']}}">{{$field['frontend_label']}} : </label>
            <span
                class="custom-field-editable"
                data-emptytext='Click to edit'
                data-id='{{$field['id']}}'
                data-dependent='{{$field['is_dependent']}}'
                data-dependentOn='{!! $field['dependent_on'] !!}'
                data-type='select' data-url='{{route("editable")}}'
                data-pk="{{$id}}" data-name='{{$field['db_field_name']}}'
                data-category='{{$field['category']}}'
                data-table='{{$table}}'
                data-source='{{json_encode($source)}}'
                data-label='{{$field['frontend_label']}}'
                data-customfield_type='{{$field['input_type']}}'
                @if(isset($details->{$field['db_field_name']}) && $details->{$field['db_field_name']} != '')
                data-value='{{ $details->{$field['db_field_name']} }}'
                @endif
                @if(!empty($field['preference_tag']))
                data-preference_tag='{{ $field['preference_tag'] }}'
                @endif
                data-required='{{($field['is_required']) ? 1 : 0}}'></span>
            @if(!empty($field['frontend_instruction']))
                <a
                    href="javascript:void(0)"
                    class='popover-dismiss'
                    title="Help"
                    data-content="{!! $field['frontend_instruction'] !!}">
                        <i class="fa fa-question-circle" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    @endif
@elseif($field['input_type'] == 'mdropdown')
@php
    $source = [];
    foreach(preg_split('/\r\n|[\r\n]/', $field['options']) as $option) {
        $source[$option]  = $option;
    }
@endphp
    @if ($label == 'call_script' && $pos !== false)
            <div id="customfield{{$field['id']}}" class="customfield {{$depedent == 1 ? 'hide' : ''}}">
                <label for="{{$field['db_field_name']}}">
                    {{substr($field['call_script'], 0, $pos)}}
                    <span
                        class="custom-field-editable"
                        data-emptytext='Click to edit'
                        data-id='{{$field['id']}}'
                        data-dependent='{{$field['is_dependent']}}'
                        data-dependentOn='{!! $field['dependent_on'] !!}'
                        data-type='select' data-url='{{route("editable")}}'
                        data-pk="{{$id}}" data-name='{{$field['db_field_name']}}'
                        data-category='{{$field['category']}}'
                        data-table='{{$table}}'
                        data-source='{{json_encode($source)}}'
                        data-label='{{$field['frontend_label']}}'
                        data-customfield_type='{{$field['input_type']}}'
                        @if(isset($details->{$field['db_field_name']}) && $details->{$field['db_field_name']} != '')
                        data-value='{{ json_encode(explode(",", $details->{$field['db_field_name']})) }}'
                        @endif
                        @if(!empty($field['preference_tag']))
                        data-preference_tag='{{ $field['preference_tag'] }}'
                        @endif
                        data-required='{{($field['is_required']) ? 1 : 0}}'
                        data-multiselectdropdown='true'
                        data-tpl='<select multiple
                        class="multiselectdropdown"
                        data-placeholder="Please Select"
                        data-live-search="true"></select>'></span>
                    {{substr($field['frontend_label'], ($pos + 7))}}
                </label>
                @if(!empty($field['frontend_instruction']))
                    <a
                        href="javascript:void(0)"
                        class='popover-dismiss'
                        title="Help"
                        data-content="{!! $field['frontend_instruction'] !!}">
                            <i class="fa fa-question-circle" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
    @else
        <div id="customfield{{$field['id']}}" class="customfield {{$depedent == 1 ? 'hide' : ''}}">
            <label for="{{$field['db_field_name']}}">{{$field['frontend_label']}} : </label>
            <span
                class="custom-field-editable"
                data-emptytext='Click to edit'
                data-id='{{$field['id']}}'
                data-dependent='{{$field['is_dependent']}}'
                data-dependentOn='{!! $field['dependent_on'] !!}'
                data-type='select' data-url='{{route("editable")}}'
                data-pk="{{$id}}" data-name='{{$field['db_field_name']}}'
                data-category='{{$field['category']}}'
                data-table='{{$table}}'
                data-source='{{json_encode($source)}}'
                data-label='{{$field['frontend_label']}}'
                data-customfield_type='{{$field['input_type']}}'
                @if(isset($details->{$field['db_field_name']}) && $details->{$field['db_field_name']} != '')
                data-value='{{ json_encode(explode(",", $details->{$field['db_field_name']})) }}'
                @endif
                @if(!empty($field['preference_tag']))
                data-preference_tag='{{ $field['preference_tag'] }}'
                @endif
                data-required='{{($field['is_required']) ? 1 : 0}}'
                data-multiselectdropdown='true'
                data-tpl='<select multiple
                class="multiselectdropdown"
                data-placeholder="Please Select"
                data-live-search="true"></select>'></span>
                @if(!empty($field['frontend_instruction']))
                <a
                    href="javascript:void(0)"
                    class='popover-dismiss'
                    title="Help"
                    data-content="{!! $field['frontend_instruction'] !!}">
                        <i class="fa fa-question-circle" aria-hidden="true"></i>
                </a>
                @endif
        </div>
    @endif
<script type="text/javascript">
    $(document).ready(function(){
        $('.editable').on('shown', function(e, editable) {
            var $input = editable && editable.input && editable.input.$input
                ? $(editable.input.$input)
                : null;

            if ($input && $input.hasClass('multiselectdropdown')) {
                $input.css('width', '100px');

                $input.multiselect({
                    includeSelectAllOption: true,
                    buttonWidth: '100px'
                });
            }
        });

        $('.editable').on('hidden', function(e, reason) {
            var editable = $(this).data('editable');
            if (editable && editable.input && editable.input.$input && editable.input.$input.hasClass('multiselectdropdown')) {
                editable.input.$input.multiselect('destroy');
            }
        });
    });
</script>
@endif
<script type="text/javascript">
$(document).ready(function(){
    $.each($(".customfield.hide"), function() {
        $(this).parent().hide();
    });
    $.each($('.custom-field-editable'), function() {
        @if(!empty($dependOn['dependent_on_field']) && !empty($dependOn['dependent_on_value']))
            if ($(this)[0].dataset.id == {{$dependOn['dependent_on_field']}}) {
                if (
                    $(this)[0].dataset.value == '{{$dependOn['dependent_on_value']}}'
                ) {
                    $('#customfield{{$dependOn['dependent_field']}}').parent().show();
                    $('#customfield{{$dependOn['dependent_field']}}').removeClass('hide');
                } else {
                    $('#customfield{{$dependOn['dependent_field']}}').parent().hide();
                    $('#customfield{{$dependOn['dependent_field']}}').addClass('hide');
                }
                $(this).on('shown', function(e, editable) {
                    if ($(editable.input.type == 'select')) {
                        $(editable.input.$input).change(function() {
                            if ($(this).find('option:selected').text() == '{{$dependOn['dependent_on_value']}}') {
                                $('#customfield{{$dependOn['dependent_field']}}').parent().show();
                                $('#customfield{{$dependOn['dependent_field']}}').removeClass('hide');
                            } else {
                                $('#customfield{{$dependOn['dependent_field']}}').parent().hide();
                                $('#customfield{{$dependOn['dependent_field']}}').addClass('hide');
                            }
                        });
                    }
                });
            }
        @endif
    });
    $('.popover-dismiss').popover({html : true});
    $('.popover-dismiss').on('click', function (e) {
        $('.popover-dismiss').not(this).popover('hide');
    });
    $('body').on('click', function (e) {
        $('.popover-dismiss').each(function () {
            if (
                !$(this).is(e.target) &&
                $(this).has(e.target).length === 0 &&
                $('.popover').has(e.target).length === 0
            ) {
                $(this).popover('hide');
            }
        });
    });
});
</script>
