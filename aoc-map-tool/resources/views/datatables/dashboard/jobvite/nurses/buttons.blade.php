@php
    /**
     * @see \App\Http\Controllers\Dashboard::getJobviteData
     */
@endphp

<a href="javascript:void(0)" onclick="showCandidateNextStepForm('{{ $id }}', '{{ $type }}')">
    <i class="fa fa-calendar" aria-hidden="true"></i>
</a> |
@if($type !== 'therapist')
<a href="javascript:void(0)" onclick="showScheduleForm('', 'candidates##{{ $id }}', '')">
    <i class="fa fa-plus" aria-hidden="true"></i>
</a> |

<a href="{{ route('goToNeedToHireMap') }}?candidateId={{ $id }}">
    <i class="fa fa-map-marker" aria-hidden="true"></i>
</a> |
@endif
<a href="javascript:void(0)" onclick="openCandidateDetails({{ $id }}, false, '{{ $type }}')">
    <i class="fa fa-eye" aria-hidden="true"></i>
</a>

@if(!empty($id))
    | <a
        data-bs-dismiss="modal"
        href="javascript:void(0);"
        onclick="event.preventDefault(); openNotes({{ $id }}, 'candidate', 'Candidate Notes Added')">
        <i class="fa fa-sticky-note-o"></i>
    </a>
@endif
