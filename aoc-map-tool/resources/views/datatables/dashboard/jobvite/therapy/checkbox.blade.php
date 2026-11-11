@php
    /**
     * @see \App\Http\Controllers\Dashboard::getJobviteData
     */
@endphp
<input
    type="checkbox"
    name="checkbox-{{ $type }}[]"
    class="form-input td select-all-{{ str_replace(' ', '_', $type) }}"
    value="{{ $id }}"
/>
