@php
    use App\Helpers\LD; //todo remove this FF condition later
@endphp

@if(LD::checkFeatureFlag('dashboard-global-location-filter-v1'))
    <span class="badge badge-global" style="display: none" title="Following the global filter">● Global</span>
    <span class="badge badge-override" style="display: none" title="This bucket uses its own filter values">● Local override</span>
@endif
