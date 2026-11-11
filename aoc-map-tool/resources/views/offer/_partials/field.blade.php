<style>
    .offer-extended-container .form-group {
        position: relative;
    }

    .offer-extended-container .form-group label {
        line-height: 0 !important;
        width: 100%;
    }

    .offer-extended-container .tabs-container .tab-content {
        max-height: none !important;
    }

    .offer-extended-container input {
        padding-left: 20px !important;
    }

    .offer-extended-container .form-group span.dollar {
        position: absolute;
        left: 10px;
        top: 18px;
        display: block;
    }

    .offer-extended-container .input-no-padding input, .offer-extended-container textarea {
        padding-left: 10px !important;
    }
</style>
@php
    $type = $data->field->value_type;
@endphp

@if($type === 'string')
    @include('offer._partials._fields.string', ['data' => $data, 'disabled' => $disabled])
@endif

@if($type === 'text')
    @include('offer._partials._fields.text', ['data' => $data, 'disabled' => $disabled])
@endif

@if($type === 'date')
    @include('offer._partials._fields.date', ['data' => $data, 'disabled' => $disabled])
@endif

@if($type === 'decimal')
    @include('offer._partials._fields.decimal', ['data' => $data, 'disabled' => $disabled])
@endif

@if($type === 'int')
    @include('offer._partials._fields.int', ['data' => $data, 'disabled' => $disabled, 'moneyFormat' => $moneyFormat ?? true])
@endif

<script>
    $('.datepicker').datepicker({
        dateFormat: 'mm/dd/yy'
    }).on('click', function () {
        $('#ui-datepicker-div').appendTo($(this).parent());
    });
</script>
