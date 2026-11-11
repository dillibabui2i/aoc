@if (!is_null($lastAvailableError))
<div>
    <b>Error:</b> code - {{ $lastAvailableError->code }}, {{ $lastAvailableError->message }}
</div>
@endif
