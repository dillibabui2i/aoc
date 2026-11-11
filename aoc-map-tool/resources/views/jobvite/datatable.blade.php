<?php

use App\Helpers\H;
use App\Helpers\LD;
use App\Enums\JobViteRecruiterJobType;

$nonStepBucket = [
    'NEW',
    'recruiter_phone_screen',
    'jpet',
    'pending_graduation_followup',
    'NEW_therapy',
    'PET_therapy',
    'recruiter_phone_screen_therapy',
    JobViteRecruiterJobType::SCHEDULED_RECRUITER_PHONE_SCREEN_THERAPY->value,
    'scheduled_clinical_interview_therapy',
    'rescheduled_clinical_interview_therapy',
    'clinical_interview_therapy',
    'offer_extended_therapy',
    'pending_graduation_follow_up_therapy',
];

$typeFunction = ucwords(str_replace(' ', '', str_replace('_', '', $type)));
$isCCMRole = H::getHelper('ConstantHelper')->getCurrentRolesByUserId(auth()->user()->id) === 34;

$assignRecruiterEnabled = LD::checkFeatureFlag('jobvite-assign-recruiter-to-job');
$candidateNotesEnabled = LD::checkFeatureFlag('jobvite-add-candidate-note');
$pastDueEnabled = LD::checkFeatureFlag('dashboard-past-due-count');
$isTherapy = str_contains($type, 'therapy');

$assignableTypes = [
    'NEW',
    'recruiter_phone_screen',
    'jpet',
    'pending_graduation_followup',
//    'NEW_therapy',
//    'PET_therapy',
//    'recruiter_phone_screen_therapy',
//    'scheduled_clinical_interview_therapy',
//    'rescheduled_clinical_interview_therapy',
//    'clinical_interview_therapy',
//    'offer_extended_therapy',
//    'pending_graduation_follow_up_therapy',
];

$showCheckbox = $assignRecruiterEnabled && in_array($type, $assignableTypes, true);

$columnsConfig = [];
$addColumn = function (
    string $key,
    string $label,
    ?string $name = null,
    array $extra = []
) use (&$columnsConfig) {
    $columnsConfig[] = array_merge([
        'key' => $key,
        'label' => $label,
        'name' => $name ?? $key,
    ], $extra);
};

if ($showCheckbox) {
    $addColumn('select', '', 'select', [
        'orderable' => false,
        'th_html' => '<input class="form-input" id="select-all-checkbox-' . $type . '" type="checkbox" name="select-' . $type . '" value="0"/>',
    ]);
}

$addColumn('primaryRecruiter', 'Primary Recruiter', 'primaryRecruiter');
$addColumn('fullName', 'Full Name', 'fullName');
$addColumn('requisition', 'Requisition', 'requisition');
$addColumn('locationCity', 'Location City', 'locationCity');

if ($isTherapy) {
    if (in_array($type, ['NEW_therapy', 'PET_therapy', 'recruiter_phone_screen_therapy', 'scheduled_recruiter_phone_screen_therapy'], true)) {
        $addColumn('application_date', 'Application Date/Time', 'application_date');
        $addColumn('mobile', 'Cell Phone', 'mobile');
        $addColumn('homePhone', 'Home Phone', 'homePhone');
        $addColumn('email', 'Email', 'mail');

        if ($type === 'recruiter_phone_screen_therapy') {
            $addColumn('FollowUpDateTime', 'Follow-up', 'candidates.FollowUpDateTime');
        }

        if ($type === JobViteRecruiterJobType::SCHEDULED_RECRUITER_PHONE_SCREEN_THERAPY->value) {
            $addColumn('scheduledRecruiterPhoneScreenDate', 'Screen Date/Time', 'candidates.scheduledRecruiterPhoneScreenDate');
        }

        if ($type === 'PET_therapy') {
            $addColumn('appWorkFlowState', 'Attempt', 'candidates.appWorkFlowState');
        }
    }

    if ($type === 'scheduled_clinical_interview_therapy') {
        $addColumn('clinicalInterviewDate', 'Interview Date/Time', 'clinicalInterviewDate');
        $addColumn('therapyClinicalManager', 'Therapy Clinical Manager', 'therapyClinicalManager');
        $addColumn('mobile', 'Cell Phone', 'mobile');
        $addColumn('homePhone', 'Home Phone', 'homePhone');
        $addColumn('email', 'Email', 'mail');
    }

    if ($type === 'rescheduled_clinical_interview_therapy') {
        $addColumn('clinicalInterviewDate', 'Interview Date/Time', 'clinicalInterviewDate');
        $addColumn('therapyClinicalManager', 'Therapy Clinical Manager', 'therapyClinicalManager');
        $addColumn('mobile', 'Cell Phone', 'mobile');
        $addColumn('homePhone', 'Home Phone', 'homePhone');
        $addColumn('email', 'Email', 'mail');
    }

    if ($type === 'clinical_interview_therapy') {
        $addColumn('clinicalInterviewDate', 'Interview Date/Time', 'clinicalInterviewDate');
        $addColumn('therapyClinicalManager', 'Therapy Clinical Manager', 'therapyClinicalManager');
        $addColumn('mobile', 'Cell Phone', 'mobile');
        $addColumn('homePhone', 'Home Phone', 'homePhone');
        $addColumn('email', 'Email', 'mail');
    }

    if ($type === 'offer_extended_therapy') {
        $addColumn('clinicalInterviewDate', 'Interview Date/Time', 'clinicalInterviewDate');
        $addColumn('therapyClinicalManager', 'Therapy Clinical Manager', 'therapyClinicalManager');
        $addColumn('mobile', 'Cell Phone', 'mobile');
        $addColumn('homePhone', 'Home Phone', 'homePhone');
        $addColumn('email', 'Email', 'mail');
        $addColumn('FollowUpDateTime', 'Follow-up', 'candidates.FollowUpDateTime');
    }

    if ($type === 'pending_graduation_follow_up_therapy') {
        $addColumn('mobile', 'Cell Phone', 'mobile');
        $addColumn('homePhone', 'Home Phone', 'homePhone');
        $addColumn('email', 'Email', 'mail');
        $addColumn('FollowUpDateTime', 'Follow-up', 'candidates.FollowUpDateTime');
        $addColumn('GraduationDate', 'Graduation date', 'candidates.GraduationDate');
    }
} else {
    $addColumn('application_date', 'Application Date/Time', 'application_date');
    $addColumn('mobile', 'Cell Phone', 'mobile');
    $addColumn('homePhone', 'Home Phone', 'homePhone');
    $addColumn('email', 'Email', 'mail');

    if (!in_array($type, ['NEW', 'jpet'], true)) {
        $addColumn('FollowUpDateTime', 'Follow Up', 'candidates.FollowUpDateTime');
    }

    if ($type === 'pending_graduation_followup') {
        $addColumn('GraduationDate', 'Graduation Date', 'candidates.GraduationDate');
    }

    if ($type === 'jpet') {
        $addColumn('appWorkFlowState', 'Attempt #', 'candidates.appWorkFlowState');
    }
}

if ($candidateNotesEnabled) {
    $addColumn('Candidate_Notes', 'Candidate Notes', 'candidates.Candidate_Notes');
}

$addColumn('Action', 'Action', 'Action', [
    'orderable' => false,
]);

$dataTableColumns = array_map(function ($column) {
    $result = [
        'data' => $column['key'],
    ];

    if (!empty($column['name'])) {
        $result['name'] = $column['name'];
    }

    if (array_key_exists('orderable', $column)) {
        $result['orderable'] = (bool)$column['orderable'];
    }

    return $result;
}, $columnsConfig);

$selectColumnIndex = null;
$primaryRecruiterIndex = null;
$actionColumnIndex = count($columnsConfig) - 1;

$applicationDateIndex = null;

foreach ($columnsConfig as $index => $column) {
    if ($column['key'] === 'select') {
        $selectColumnIndex = $index;
    }
    if ($column['key'] === 'primaryRecruiter') {
        $primaryRecruiterIndex = $index;
    }
    if ($column['key'] === 'application_date') {
        $applicationDateIndex = $index;
    }
}

$defaultSortTypes = [JobViteRecruiterJobType::SCHEDULED_RECRUITER_PHONE_SCREEN_THERAPY->value];
$defaultOrderIndex = in_array($type, $defaultSortTypes, true) && $applicationDateIndex !== null
    ? $applicationDateIndex
    : ($primaryRecruiterIndex ?? 0);

$idPrefix = $idPrefix ?? '';
$filterFormId = $filterFormId ?? '';
$tableId = 'dataTable-jobvite-' . $idPrefix . str_replace(' ', '_', $type);
?>
<div class="table-responsive">
    <table class="table table-bordered" id="{{ $tableId }}" width="100%" cellspacing="0">
        <thead>
        <tr>
            @foreach($columnsConfig as $column)
                <td>
                    {!! $column['th_html'] ?? e($column['label']) !!}
                </td>
            @endforeach
        </tr>
        </thead>
    </table>
</div>

<script>
    @if($showCheckbox)
    var {{ $type }}_bucket_datatable;

    function showHideUdateButtonFor{{ $typeFunction }}(isChecked) {
        $('#{{ $tableId }}_wrapper div.updateall').remove();

        if (isChecked) {
            let html = '<div style="display: inline-block;" class="dataTables_length updateall">';
            @if(in_array($type, $nonStepBucket, true))
                html += '<button type="button" onclick="showRecruiterForm(\'{{ $type }}\', \'-1\', this)" class="btn btn-primary">Update Selected</button>';
            @endif
                html += '</div>';

            $('#{{ $tableId }}_wrapper').prepend(html);
        }
    }

    $(document).ready(function () {
        $('#select-all-checkbox-{{ $type }}').on('click', function () {
            $('.select-all-{{ $type }}.td').prop('checked', $(this).prop('checked'));
            showHideUdateButtonFor{{ $typeFunction }}($(this).is(':checked'));
        });
    });
    @endif

    function {{ str_replace(' ', '_', $idPrefix . $type) }}() {
        let type = '{{ $type }}';

        let columns = @json($dataTableColumns);

        let columnDefs = [];

        @if($selectColumnIndex !== null)
        columnDefs.push({
            orderable: false,
            className: 'select-checkbox',
            targets: {{ $selectColumnIndex }}
        });
        @endif

        @if($primaryRecruiterIndex !== null)
        columnDefs.push({
            className: 'dt-nowrap',
            targets: {{ $primaryRecruiterIndex }}
        });
        @endif

        columnDefs.push({
            className: 'dt-nowrap',
            targets: {{ $actionColumnIndex }}
        });

        let tableInstance = $("#{{ $tableId }}").DataTable({
            order: [[{{ $defaultOrderIndex }}, "asc"]],
            columnDefs: columnDefs,
            ajax: {
                url: "{{ route('dashboard.getjobvitedata') }}?type={{ $type }}",
                data: function (d) {
                    @if(!empty($filterFormId))
                    let filterForm = '{{ $filterFormId }}';
                    @else
                    let filterForm = '#dashboardjobvite';

                    if (type.indexOf('therapy') !== -1) {
                        filterForm = '#dashboardjobvitetherapy';
                    }
                    @endif

                    let filter = $(filterForm).serializeArray();

                    if (type === 'jpet') {
                        filter = $('#dashboardjobvitepet, #dashboardjobvite').serializeArray();
                    }

                    d.filters = filter;
                }
            },
            deferRender: true,
            processing: true,
            serverSide: true,
            columns: columns,
            drawCallback: function () {
                @if($pastDueEnabled)
                let api = this.api();
                let json = api.ajax.json();
                $("#{{ str_replace(' ', '_', $idPrefix . $type) }}-counter").html(
                    "<span>(" + tableInstance.page.info().recordsTotal + " | " + json.pastduecount + " Past Due)</span>"
                );
                @else
                $("#{{ str_replace(' ', '_', $idPrefix . $type) }}-counter").html(
                    "(" + tableInstance.page.info().recordsTotal + ")"
                );
                @endif

                @if($showCheckbox)
                $('.select-all-{{ $type }}.td').off('click').on('click', function () {
                    showHideUdateButtonFor{{ $typeFunction }}($('.select-all-{{ $type }}.td:checked').length > 0);
                });
                @endif
            },
            initComplete: function () {
                let searchInput = $("#{{ $tableId }}_filter input");
                let searchDelay = null;

                searchInput.off("keyup.DT input.DT");
                searchInput.on("keyup", function (e) {
                    let search = searchInput.val();

                    clearTimeout(searchDelay);
                    searchDelay = setTimeout(function () {
                        if (search !== null && search.length >= 3) {
                            tableInstance.search(search).draw();
                        }

                        if (search.length === 0 && e.keyCode === 8) {
                            tableInstance.search(search).draw();
                        }
                    }, dataTableDelay);
                });
            }
        });

        @if($showCheckbox)
            {{ $type }}_bucket_datatable = tableInstance;
        @endif
    }

    @if($showCheckbox)
    function showRecruiterForm(type, id = 0, button) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        let ids = 0;

        if (id > 0) {
            ids = id;
        } else if (id < 0) {
            let selectedIds = [];

            $('.select-all-' + type + '.td:checked').each(function () {
                selectedIds.push(this.value);
            });

            ids = selectedIds.join(",");
        }

        button.disabled = true;

        $.ajax({
            url: "{{ route('jobvite.assignrecruiterform') }}",
            type: "GET",
            data: {
                type: type,
                _token: _token,
                ids: ids
            },
            success: function (response) {
                button.disabled = false;

                if (response) {
                    $("#tempModalCnt").remove();

                    let $div = $('<div />').appendTo('body');
                    $div.attr('id', 'tempModalCnt').html(response);

                    $('#assignjobrecruiter').modal('show');
                    if ($(".modal:visible").length) {
                        $(document.body).addClass("modal-open");
                    }
                }
            },
            error: function () {
                button.disabled = false;
            }
        });
    }
    @endif
</script>
