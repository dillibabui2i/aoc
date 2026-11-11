<div class="mb-3">
    <label class="form-label">{{ $data->field->label }}</label>
    <input type="text" class="form-control required-field" name="{{ $data->key }}" value="{{ $data->value_string }}" {{ $disabled ? 'disabled' : '' }} aria-describedby="basic-addon2">
</div>
