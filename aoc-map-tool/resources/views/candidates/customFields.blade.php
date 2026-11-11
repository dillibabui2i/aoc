<div class="row pt-3">
    <div class="col-md-12 text-center">
        <h4 class="font-weight-bold">Custom Fields</h4>
        <hr />
    </div>
</div>

<div class="row">
    @foreach($customFields as $field)
        @php $isState = $field->db_field_name === 'aoc_cf_state'; @endphp
        <div class="form-group col-md-6"  @if($isState) style="display:none;" @endif>
            <label for="{{ $field->db_field_name }}">{{ $field->frontend_label }}</label>

            {{-- text input --}}
            @if($field->input_type === 'text')
                <input type="text" class="form-control editable-field"
                       name="custom_fields[{{ $field->db_field_name }}]"
                       id="{{ $field->db_field_name }}">
            @endif

            {{-- textarea --}}
            @if($field->input_type === 'textarea')
                <textarea
                        class="form-control editable-field"
                        name="custom_fields[{{ $field->db_field_name }}]"
                        id="{{ $field->db_field_name }}"
                        rows="3">
                </textarea>
            @endif

            {{-- date --}}
            @if($field->input_type === 'date')
                <input type="text"
                       class="form-control editable-field datepicker"
                       name="custom_fields[{{ $field->db_field_name }}]"
                       id="{{ $field->db_field_name }}"
                       autocomplete="off">
            @endif

            {{-- boolean --}}
            @if($field->input_type === 'boolean')
                <select name="custom_fields[{{ $field->db_field_name }}]"
                        id="{{ $field->db_field_name }}"
                        class="form-control editable-field">
                    <option value="">Select One</option>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            @endif

            {{-- single select --}}
            @if($field->input_type === 'dropdown')
                <select name="custom_fields[{{ $field->db_field_name }}]"
                        id="{{ $field->db_field_name }}"
                        class="form-control editable-field">
                    <option value="">Select One</option>
                    @foreach(explode("\n", trim($field->options)) as $option)
                        <option value="{{ trim($option) }}">{{ trim($option) }}</option>
                    @endforeach
                </select>
            @endif

            {{-- multi select --}}
            @if($field->input_type === 'mdropdown')
                <select name="custom_fields[{{ $field->db_field_name }}][]"
                        id="{{ $field->db_field_name }}"
                        class="form-control multiselect-editable editable-field"
                        multiple>
                    @foreach(explode("\n", trim($field->options)) as $option)
                        <option value="{{ trim($option) }}">{{ trim($option) }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    @endforeach
</div>