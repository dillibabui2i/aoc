@php
    use App\Enums\ConsentTemplates\StatusEnum;
@endphp

<span class="{{ $row->status === StatusEnum::PUBLISHED->value ? 'text-success' : 'text-dark' }}" style="font-weight: bold">
    {{ $row->status }}
</span>
