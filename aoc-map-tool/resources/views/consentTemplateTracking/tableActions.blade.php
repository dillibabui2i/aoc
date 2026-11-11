@php
    use App\Enums\ConsentTemplates\StatusEnum;

    /** @var \App\ConsentTemplate $row */
    $status = $row->status;
@endphp

@if ($status === StatusEnum::DRAFT->value)
    <a
        href="javascript:void(0);"
        title="Edit"
        class="js-consent-open-modal"
        data-id="{{ $row->id }}"
        data-mode="edit"
        data-show-url="{{ route('consent.tracking.show', $row->id) }}"
        data-delete-url="{{ route('consent.tracking.destroy', $row->id) }}"
    >
        <i class="fa fa-pencil" aria-hidden="true"></i>
    </a>

@elseif (in_array($status, [StatusEnum::PUBLISHED->value, StatusEnum::SUPERSEDED->value], true))
    <a
        href="javascript:void(0);"
        title="View"
        class="js-consent-open-modal"
        data-id="{{ $row->id }}"
        data-mode="view"
        data-show-url="{{ route('consent.tracking.show', $row->id) }}"
    >
        <i class="fa fa-eye" aria-hidden="true"></i>
    </a>
@endif
