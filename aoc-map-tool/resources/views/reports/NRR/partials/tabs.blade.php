@php
///todo need to be moved out of here
    $types = [
        'candidates' => 'Candidates',
        'IPEAs' => 'IPEAs',
        'PEPIs' => 'PEPIs',
        'WEPIs' => 'WEPIs',
        'candidateMatch' => 'CM',
        'hoursRemoved' => 'Hours Removed',
        'candidateStarts' => 'Candidate Starts',
        'grossMargin' => 'Gross Margin'
    ];
@endphp

<div id="tabs" class="ui-tabs ui-corner-all ui-widget ui-widget-content">
    <ul class="ui-tabs-nav ui-corner-all ui-helper-reset ui-helper-clearfix ui-widget-header">
        @foreach ($types as $id => $type)
            <li class="ui-tabs-tab ui-corner-top ui-state-default ui-tab @php if(Route::currentRouteName() == $id): @endphp ui-tabs-active ui-state-active@php endif; @endphp">
                <a href="{{route($id)}}"
                   class="ui-tabs-anchor">@php echo ucfirst($type); @endphp</a></li>
        @endforeach
    </ul>
</div>
