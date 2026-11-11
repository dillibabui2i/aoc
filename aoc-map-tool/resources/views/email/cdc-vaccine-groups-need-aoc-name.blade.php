<p>Hello,</p>
<p>The monthly CDC vaccine sync found vaccine groups that have no AOC name. Please fill in the AOC name for:</p>
<ul>
    @foreach ($groups as $group)
        <li>{{ $group['cvx_group_code'] }} &ndash; {{ $group['cdc_name'] }}</li>
    @endforeach
</ul>
