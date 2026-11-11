<ul>
    @foreach($days as $dayName => $dayDate)
        @php
            $isDisabled = $data[$dayName]['disabled'] ?? false;
        @endphp

        <li {{$isDisabled ? 'class=disabled-item' : ''}}>
            <a href="#tab-{{ $dayName }}">
                {{ ucfirst($dayName) }}
            </a>
        </li>
    @endforeach
</ul>
