@if(str_contains($jobCategoryType, 'therap'))
    @include('candidates.partials.titles-therapy')
@else
    @include('candidates.partials.titles-nurse')
@endif
