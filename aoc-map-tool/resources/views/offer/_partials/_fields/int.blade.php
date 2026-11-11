<div class="mb-3" style="position: relative">
    <label class="form-label">{{ $data->field->label }}</label>
    @if ($moneyFormat)
        <span class="dollar">$</span>
    @endif
    <input type="text" class="form-control required-field" name="{{ $data->key }}" value="{{ $data->value_int }}" {{ $disabled ? 'disabled' : '' }} aria-describedby="basic-addon2">
</div>
