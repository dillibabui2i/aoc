
<style>
.at-capacity-banner {
    background: color-mix(in srgb, #e3342f 20%, transparent);
    border-left: 3px solid #e3342f;
    color: #e3342f;
    font-weight: 700;
    padding: 8px 12px;
    margin-bottom: 10px;
    border-radius: 4px;
}
.at-capacity-count {
    color: #e3342f;
    font-weight: 700;
}
</style>
<x-modal>
    <x-slot name="id">getTrachVentClassInfoModal</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">Class Details</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row p-0 d-flex nurse-info-cnt" id="trachVentClassInfoData"
            data-state="{{ $classDetails->branchState }}"
            data-branch-id="{{ $classDetails->locationId }}"
            data-class-date="{{ $classDetails->classDate }}">
            @if($classDetails->isFull)
                <div class="col-md-12 p-0">
                    <div class="at-capacity-banner">
                        🚫 At Capacity — This class is full ({{ $classDetails->enrolledCount }}/{{ $classDetails->capacity }})
                    </div>
                </div>
            @endif
            @if($classDetails->isLastOccurrence)
                <div class="col-md-12 p-0">
                    <div class="at-capacity-banner">
                        This is the last class of this recurring series.
                    </div>
                </div>
            @endif
            <div class="col-md-12 p-0 tracker-details">
                    <div class="col-md-12 p-0 d-flex">
                        <dl class="col-md-6 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Class Type: </dt>
                            <dd class="col-sm-8 text-left">
                                {{ $classDetails->classCategoryName }}
                                @if($classDetails->isRecurring)
                                    <span class="recurring-indicator" title="Recurring class">&#128260;</span>
                                @endif
                            </dd>
                        </dl>
                        <dl class="col-md-6 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Instructor Name: </dt>
                            <dd class="col-sm-8 text-left">{{ $classDetails->instructorName }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-12 p-0 d-flex">
                        <dl class="col-md-6 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Class Date:</dt>
                            <dd class='col-sm-8 text-left'>
                                {{ \App\Helpers\H::getFormatedDate($classDetails->classDate) }}
                            </dd>
                        </dl>
                        <dl class="col-md-6 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Class Time:</dt>
                            <dd class='col-sm-8 text-left'>
                            {{ getFormatedTimeRange($classDetails->classTime, $classDetails->endTime) }}
                            </dd>
                        </dl>
                    </div>
                    @if($classDetails->isRecurring)
                        <div class="col-md-12 p-0 d-flex">
                            <dl class="col-md-6 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Recurrence:</dt>
                                <dd class="col-sm-8 text-left">
                                    Repeats {{ ucfirst($classDetails->repeatEvery->value) }}
                                    @if($classDetails->repeatEvery === \App\Enums\TrachVentClassRepeatEvery::DAY && $classDetails->repeatOnDays)
                                        on {{ implode(', ', array_map(fn ($day) => ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][(int) $day] ?? '', $classDetails->repeatOnDays)) }}
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    @endif
                    <div class="col-md-12 p-0 d-flex">
                        <dl class="col-md-6 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Capacity:</dt>
                            <dd class='col-sm-8 text-left{{ $classDetails->isFull ? ' at-capacity-count' : '' }}' id="trachVentClassCapacityDisplay">
                                {{ $classDetails->enrolledCount }}/{{ $classDetails->capacity }}
                                @if($classDetails->isFull)
                                    &nbsp;●&nbsp;Full
                                @endif
                            </dd>
                        </dl>
                    </div>
            </div>
            <div class="col-md-12 p-0 tracker-details">
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0 mr-2">Enrolled Clinicians</h5>
                    @if($classDetails->canScheduleClinician && !$classDetails->isFull)
                        <a title="Schedule a Clinician" href="#" data-trach-vent-class-id="{{ $classDetails->occurrenceId }}">
                            <i class="fa fa-plus-circle"></i>
                        </a>
                        <span class="ml-2">Schedule a Clinician</span>
                    @endif
                </div>
                <table class="table bg-white">
                    <tr>
                        <th>Clinician</th>
                        <th>Discipline</th>
                        <th>Phone Number</th>
                        <th>Client</th>
                        <th>Start Date</th>
                        @if($classDetails->canScheduleClinician && count($classRequestDetails) > 0)
                            <th>Actions</th>
                        @endif
                    </tr>
                    @if(count($classRequestDetails) === 0)
                        <tr>
                            <td colspan="5" class="text-center text-muted">No clinicians enrolled in this class.</td>
                        </tr>
                    @endif
                    <?php foreach ($classRequestDetails as $classRequestData) { ?>
                        <tr>
                            <td><?php echo $classRequestData->display_name ?></td>
                            <td><?php echo $classRequestData->display_discipline ?></td>
                            <td><?php echo $classRequestData->display_phone ?></td>
                            <td><?php echo $classRequestData->display_client_name ?></td>
                            <td><?php if($classRequestData->display_start_date != '00/00/0000' && $classRequestData->display_start_date != NULL && $classRequestData->display_start_date !='') {
                                    echo $classRequestData->display_start_date;
                                } ?>
                            </td>
                            @if($classDetails->canScheduleClinician)
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary registration-action-btn reschedule-registration-btn"
                                        data-registration-id="{{ $classRequestData->id }}"
                                        data-clinician-name="{{ $classRequestData->display_name }}"
                                        data-clinician-discipline="{{ $classRequestData->display_discipline }}">Reschedule</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger registration-action-btn cancel-registration-btn"
                                        data-registration-id="{{ $classRequestData->id }}"
                                        data-clinician-name="{{ $classRequestData->display_name }}"
                                        data-clinician-discipline="{{ $classRequestData->display_discipline }}">Cancel</button>
                                </td>
                            @endif
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer">
        @if($classDetails->canCancelOccurrence)
            <a
            class="mb-0 mr-auto text-danger border border-danger rounded px-2 py-3 text-decoration-none cancel-occurrence-btn"
            title="Cancel Class" href="#"
            data-occurrence-id="{{ $classDetails->occurrenceId }}"
            data-class-type="{{ $classDetails->classCategoryName }}"
            data-class-date="{{ \App\Helpers\H::getFormatedDate($classDetails->classDate) }}"
            data-class-time="{{ getFormatedTimeRange($classDetails->classTime, $classDetails->endTime) }}"
            data-is-recurring="{{ $classDetails->isRecurring ? '1' : '0' }}"
            >
                <i class="fa fa-trash-o" aria-hidden="true"></i> Cancel Class
            </a>
        @endif
    </x-slot>
</x-modal>

@if($classDetails->canScheduleClinician)
    @include('trachventclass.schedule-clinician-modal')
    @include('trachventclass.registration-actions-modals')
@endif

@if($classDetails->canCancelOccurrence)
    @include('trachventclass.cancel-occurrence')
@endif
