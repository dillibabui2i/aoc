@props(['icon', 'color'])

@php
    $svgPath = match ($icon) {
        'diamond' => '<rect x="3" y="3" width="10" height="10" transform="rotate(45 8 8)" fill="'.$color.'"/>',
        'teardrop' => '<path d="M8 15 C8 15 2 10 2 6 C2 3.2 4.7 1 8 1 C11.3 1 14 3.2 14 6 C14 10 8 15 8 15Z" fill="'.$color.'" stroke="#ffffff" stroke-width="0.75"/>',
        'square' => '<rect x="3" y="3" width="10" height="10" rx="2" fill="'.$color.'"/>',
        default => '<circle cx="8" cy="8" r="6" fill="'.$color.'"/>',
    };
@endphp

<svg width="18" height="18" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
    {!! $svgPath !!}
</svg>