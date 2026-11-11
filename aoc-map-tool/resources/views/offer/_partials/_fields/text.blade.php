<div class="mb-3">
        <label class="form-label">{{ $data->field->label }}</label>
    <textarea class="form-control required-field" rows="5" name="{{ $data->key }}" {{ $disabled ? 'disabled' : '' }}>{{ $data->value_string }}</textarea>
</div>
