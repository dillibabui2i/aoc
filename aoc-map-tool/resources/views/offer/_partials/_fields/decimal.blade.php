<div class="mb-3" style="position: relative">
    <label class="form-label">{{ $data->field->label }}</label>
    <span class="dollar">$</span>
    <input type="text" class="form-control required-field" name="{{ $data->key }}" value="{{ $data->value_decimal }}" {{ $disabled ? 'disabled' : '' }} aria-describedby="basic-addon2">
</div>
