<div class="mb-3">
    <label class="form-label">{{ $data->field->label }}</label>
    <input type="text" class="form-control required-field datepicker" name="{{ $data->key }}" value="{{ $data->value_date?->format('m/d/Y') }}" {{ $disabled ? 'disabled' : '' }} autocomplete="off" aria-describedby="basic-addon2">
</div>
