<?php
use App\Enums\WeekDayEnum;
use App\Helpers\H;
?>
<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<link href="{{ mix('css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
<script>
// Loaded synchronously: jQuery UI's datepicker (loaded in the page <head>) also
// defines $.fn.datepicker, so a plain <script src> here would race against the
// inline .datepicker() calls below and could lose. Same workaround used by the
// PRC Date/Time fields on the Schedule Next Step / Add Clinician form.
$.ajax({
    url: "{{ mix('js/bootstrap-datepicker.min.js') }}",
    dataType: 'script',
    async: false
});
</script>

<style>
.manage-schedule-plus {
    margin-left: 8px;
}
table.prc-block-table td, table.prc-block-table th {
    padding: 6px 8px;
    vertical-align: middle;
}
.prc-block-remove {
    color: #c9302c;
    cursor: pointer;
}
.prc-capacity-banner {
    background: #FDF6DE;
    border: 1px solid #EADFAE;
    color: #8a6d1f;
    font-size: 13px;
    padding: 10px 14px;
    border-radius: 4px;
    margin-bottom: 18px;
}
.prc-capacity-day {
    border: 1px solid #d5d9de;
    border-radius: 5px;
    margin-bottom: 12px;
    overflow: hidden;
}
.prc-capacity-day-header {
    background: #f6f7f9;
    padding: 8px 12px;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #d5d9de;
}
.prc-capacity-add {
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}
.prc-capacity-day table {
    margin-bottom: 0;
}
.prc-capacity-day .prc-capacity-input {
    width: 90px;
    text-align: center;
}
.prc-capacity-empty {
    padding: 10px 12px;
    font-size: 12.5px;
}
</style>

<a href="javascript:void(0);" class="manage-schedule-plus" title="Manage Schedule" onclick="openManageScheduleModal();">
    <i class="fa fa-plus-circle"></i>
</a>

<x-modal>
    <x-slot name="id">manageScheduleModal</x-slot>
    <x-slot name="size">w70</x-slot>
    <x-slot name="title">Manage Branch Capacity &amp; Blocked Availability</x-slot>
    <x-slot name="body">
        <div id="manageScheduleAlert"></div>

        <div class="form-group row">
            <div class="col-md-6">
                <label for="manageScheduleBranch">Branch <abbr class="text-danger" title="This is required">*</abbr></label>
                <select class="form-control" id="manageScheduleBranch">
                    <option value="">Select Branch</option>
                    @foreach($prcAvailabilityFacilities as $facility)
                    <option value="{{ $facility->id }}">{{ $facility->FacilityName }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="manageScheduleBlockedSection" style="display:none">
            <div class="prc-capacity-banner">
                Set the recurring number of PRCs this branch can handle per hour. This is the branch's normal
                operating capacity &mdash; use <strong>Blocked Availability</strong> at the bottom of this form
                for one-off exceptions on specific dates.
            </div>

            <h5 class="mt-3">Weekly Capacity Template</h5>
            <p class="text-muted small">
                Set one or more time segments per day, each with its own hourly capacity.
                Leave a day with no segments to mark it fully unavailable.
            </p>
            @foreach(WeekDayEnum::cases() as $weekDay)
            <div class="prc-capacity-day" data-day="{{ $weekDay->value }}" data-label="{{ $weekDay->label() }}">
                <div class="prc-capacity-day-header">
                    {{ $weekDay->label() }}
                    <a href="javascript:void(0);" class="prc-capacity-add">+ Add Segment</a>
                </div>
                <table class="table prc-block-table prc-capacity-table" style="display:none">
                    <thead>
                        <tr>
                            <th style="width:30%">Start</th>
                            <th style="width:30%">End</th>
                            <th style="width:25%">Capacity / hr</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div class="prc-capacity-empty text-muted">
                    No segments added &mdash; {{ $weekDay->label() }} is fully unavailable for this branch.
                </div>
            </div>
            @endforeach

            <hr class="my-4">

            <h5 class="mt-3">Blocked Availability</h5>
            <p class="text-muted small">
                One-off dates and times PRCs cannot be scheduled for this branch, even if the time falls inside
                the Weekly Capacity Template above. The weekly template is not changed.
            </p>
            <table class="table prc-block-table">
                <thead>
                    <tr>
                        <th style="width:170px">Date</th>
                        <th style="width:140px">Start</th>
                        <th style="width:140px">End</th>
                        <th>Notes</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody id="prcBlockBody"></tbody>
            </table>
            <div id="prcBlockEmpty" class="text-muted small" style="display:none">No blocked availability added.</div>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addManageScheduleBlockRow();">+ Add More</button>
        </div>
        <div id="manageScheduleNoBranch" class="text-muted small">
            Select a branch to view its capacity and blocked availability.
        </div>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-primary" onclick="saveManageSchedule();">Save</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
    </x-slot>
</x-modal>

<script>
var manageScheduleState = {
    facilityId: null,
    blocks: [],
    segments: []
};

// Same time-picker constraint used for PRC scheduling on the Schedule Next Step /
// Add Clinician form: 15-minute steps, 8:00am-5:00pm, default am/pm display.
var PRC_TIMEPICKER_OPTIONS = {
    step: 15,
    forceRoundTime: true,
    minTime: '08:00:00',
    maxTime: '17:00:00'
};

// Weekly capacity is configured per hour, so its segments are whole hours only
// (8:00am, 9:00am ... 5:00pm) - unlike Blocked Availability's 15-minute steps.
var PRC_CAPACITY_TIMEPICKER_OPTIONS = {
    step: 60,
    forceRoundTime: true,
    minTime: '08:00:00',
    maxTime: '17:00:00'
};

function toDisplayDate(isoDate) {
    if (!isoDate) {
        return '';
    }
    var parts = isoDate.split('-');
    return parts.length === 3 ? (parts[1] + '/' + parts[2] + '/' + parts[0]) : '';
}
function toIsoDate(displayDate) {
    if (!displayDate) {
        return '';
    }
    var parts = displayDate.split('/');
    return parts.length === 3 ? (parts[2] + '-' + parts[0] + '-' + parts[1]) : '';
}
function todayIsoDate() {
    var now = new Date();
    return now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
}

// The backend stores/returns times as 24hr "HH:MM"; the timepicker widget displays
// and emits am/pm strings (e.g. "6:00am") - convert at the load/compare boundaries.
function toAmPm(hhmm) {
    if (!hhmm) {
        return '';
    }
    var parts = hhmm.split(':');
    var hour = parseInt(parts[0], 10);
    var displayHour = hour % 12;
    if (displayHour === 0) {
        displayHour = 12;
    }
    return displayHour + ':' + parts[1] + (hour >= 12 ? 'pm' : 'am');
}
function timeStrToMinutes(str) {
    if (!str) {
        return null;
    }
    var m = /^(\d{1,2}):(\d{2})\s*([ap]m)?$/i.exec(str.trim());
    if (!m) {
        return null;
    }
    var hour = parseInt(m[1], 10);
    var minutes = parseInt(m[2], 10);
    var ampm = m[3] ? m[3].toLowerCase() : null;
    if (ampm === 'pm' && hour < 12) {
        hour += 12;
    }
    if (ampm === 'am' && hour === 12) {
        hour = 0;
    }
    return hour * 60 + minutes;
}

// Locks the End picker until a Start time is chosen, and disallows picking an
// End time at or before the chosen Start time.
function applyEndTimeConstraint($endEl, startVal) {
    if (!startVal) {
        $endEl.val('').prop('disabled', true);
        $endEl.timepicker('option', 'disableTimeRanges', []);
        return;
    }
    $endEl.prop('disabled', false);
    $endEl.timepicker('option', 'disableTimeRanges', [['12:00am', startVal]]);
}

function openManageScheduleModal() {
    $('#manageScheduleAlert').html('');
    manageScheduleState.facilityId = null;
    manageScheduleState.blocks = [];
    manageScheduleState.segments = [];
    $('#manageScheduleBranch').val('');
    $('#manageScheduleBlockedSection').hide();
    $('#manageScheduleNoBranch').show();
    $('#manageScheduleModal').modal('show');
}

// bootstrap-datepicker/jquery.timepicker position their popup once, on open, and
// don't re-track it on scroll - inside this modal (which scrolls itself via
// Bootstrap's own .modal{overflow-y:auto}), that leaves the popup visually
// detached from the input as the modal scrolls. Just hide it on scroll.
$('#manageScheduleModal').on('scroll', function() {
    $('.prc-datepicker').datepicker('hide');
    $('.prc-timepicker').timepicker('hide');
});

function loadManageScheduleAvailability(facilityId) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{ route('ccrAvailability.get') }}",
        type: 'get',
        data: { facility_id: facilityId, _token: _token },
        success: function(response) {
            if (response.success != 1) {
                return;
            }
            manageScheduleState.blocks = response.blocks.map(function(row) {
                return { block_date: row.block_date, start_time: toAmPm(row.start_time), end_time: toAmPm(row.end_time), note: row.note || '' };
            });
            manageScheduleState.segments = (response.segments || []).map(function(row) {
                return {
                    day_of_week: row.day_of_week,
                    start_time: toAmPm(row.start_time),
                    end_time: toAmPm(row.end_time),
                    capacity_per_hour: row.capacity_per_hour
                };
            });
            renderManageScheduleBlocks();
            renderManageScheduleSegments();
        }
    });
}

function initPrcTimepicker($el, onChange, options) {
    $el.timepicker(options || PRC_TIMEPICKER_OPTIONS).on('change', function() {
        onChange($(this).val());
    });
}

function escapeAttr(value) {
    return String(value === null || value === undefined ? '' : value).replace(/"/g, '&quot;');
}

// Renders each day's capacity segments into its own day block. Each row keeps the
// index of its segment in manageScheduleState.segments (one flat list for all days).
function renderManageScheduleSegments() {
    $('.prc-capacity-day').each(function() {
        var $day = $(this);
        var day = parseInt($day.data('day'), 10);
        var html = '';

        manageScheduleState.segments.forEach(function(segment, i) {
            if (segment.day_of_week !== day) {
                return;
            }
            var timeInput = '<input type="text" autocomplete="off" class="form-control prc-timepicker ';
            html += '<tr data-i="' + i + '">'
                + '<td>' + timeInput + 'prc-capacity-start" value="' + escapeAttr(segment.start_time) + '"></td>'
                + '<td>' + timeInput + 'prc-capacity-end"' + (segment.start_time ? '' : ' disabled')
                + ' value="' + escapeAttr(segment.end_time) + '"></td>'
                + '<td><input type="number" min="1" max="99" step="1" class="form-control prc-capacity-input"'
                + ' value="' + escapeAttr(segment.capacity_per_hour) + '"></td>'
                + '<td><span class="prc-block-remove" title="Remove"><i class="fa fa-trash"></i></span></td>'
                + '</tr>';
        });

        $day.find('tbody').html(html);
        $day.find('.prc-capacity-table').toggle(html !== '');
        $day.find('.prc-capacity-empty').toggle(html === '');
    });

    $('.prc-capacity-table tbody tr').each(function() {
        var i = $(this).data('i');
        var $end = $(this).find('.prc-capacity-end');

        initPrcTimepicker($(this).find('.prc-capacity-start'), function(val) {
            manageScheduleState.segments[i].start_time = val;
            manageScheduleState.segments[i].end_time = '';
            applyEndTimeConstraint($end, val);
        }, PRC_CAPACITY_TIMEPICKER_OPTIONS);
        initPrcTimepicker($end, function(val) {
            manageScheduleState.segments[i].end_time = val;
        }, PRC_CAPACITY_TIMEPICKER_OPTIONS);
        applyEndTimeConstraint($end, manageScheduleState.segments[i].start_time);

        $(this).find('.prc-capacity-input').on('input', function() {
            manageScheduleState.segments[i].capacity_per_hour = $(this).val();
        });
        $(this).find('.prc-block-remove').on('click', function() {
            manageScheduleState.segments.splice(i, 1);
            renderManageScheduleSegments();
        });
    });
}

$(document).on('click', '.prc-capacity-add', function() {
    var day = parseInt($(this).closest('.prc-capacity-day').data('day'), 10);
    manageScheduleState.segments.push({ day_of_week: day, start_time: '', end_time: '', capacity_per_hour: '' });
    renderManageScheduleSegments();
});

function dayLabel(day) {
    return $('.prc-capacity-day[data-day="' + day + '"]').data('label');
}

// Mirrors SaveAvailabilityRequest's weekly capacity rules.
function validateManageScheduleSegments() {
    var errors = [];
    var byDay = {};

    manageScheduleState.segments.forEach(function(segment) {
        var label = dayLabel(segment.day_of_week) + ': ';
        var start = timeStrToMinutes(segment.start_time);
        var end = timeStrToMinutes(segment.end_time);
        var capacity = String(segment.capacity_per_hour).trim();

        if (start === null || end === null) {
            errors.push(label + 'Start and End time are both required.');
        } else if (end <= start) {
            errors.push(label + 'End time must be later than Start time.');
        } else if (start % 60 !== 0 || end % 60 !== 0) {
            errors.push(label + 'Start and End time must be on the hour.');
        } else {
            (byDay[segment.day_of_week] = byDay[segment.day_of_week] || []).push({ start: start, end: end });
        }
        if (!/^\d+$/.test(capacity) || parseInt(capacity, 10) < 1 || parseInt(capacity, 10) > 99) {
            errors.push(label + 'Capacity per hour must be a whole number from 1 to 99.');
        }
    });

    Object.keys(byDay).forEach(function(day) {
        var ranges = byDay[day].sort(function(a, b) { return a.start - b.start; });
        for (var i = 1; i < ranges.length; i++) {
            if (ranges[i].start < ranges[i - 1].end) {
                errors.push(dayLabel(day) + ': time segments must not overlap.');
                break;
            }
        }
    });

    return errors;
}

function renderManageScheduleBlocks() {
    var html = '';
    manageScheduleState.blocks.forEach(function(block, i) {
        html += '<tr data-i="' + i + '">'
            + '<td><input type="text" autocomplete="off" class="form-control prc-datepicker prc-block-date" value="' + toDisplayDate(block.block_date) + '"></td>'
            + '<td><input type="text" autocomplete="off" class="form-control prc-timepicker prc-block-start" value="' + block.start_time + '"></td>'
            + '<td><input type="text" autocomplete="off" class="form-control prc-timepicker prc-block-end"' + (block.start_time ? '' : ' disabled') + ' value="' + block.end_time + '"></td>'
            + '<td><input type="text" class="form-control prc-block-note" placeholder="e.g. PTO, team meeting" value="' + (block.note || '').replace(/"/g, '&quot;') + '"></td>'
            + '<td><span class="prc-block-remove" title="Remove"><i class="fa fa-trash"></i></span></td>'
            + '</tr>';
    });
    $('#prcBlockBody').html(html);
    $('#prcBlockEmpty').toggle(manageScheduleState.blocks.length === 0);

    $('#prcBlockBody tr').each(function() {
        var i = $(this).data('i');

        $(this).find('.prc-block-date').datepicker({
            format: 'mm/dd/yyyy',
            startDate: new Date(),
            todayHighlight: true
        }).bind('click', function() {
            $('#ui-datepicker-div').appendTo($(this).parent());
        }).on('changeDate', function() {
            manageScheduleState.blocks[i].block_date = toIsoDate($(this).val());
        });

        var $end = $(this).find('.prc-block-end');
        initPrcTimepicker($(this).find('.prc-block-start'), function(val) {
            manageScheduleState.blocks[i].start_time = val;
            manageScheduleState.blocks[i].end_time = '';
            applyEndTimeConstraint($end, val);
        });
        initPrcTimepicker($end, function(val) { manageScheduleState.blocks[i].end_time = val; });
        applyEndTimeConstraint($end, manageScheduleState.blocks[i].start_time);

        $(this).find('.prc-block-note').on('input', function() { manageScheduleState.blocks[i].note = $(this).val(); });
        $(this).find('.prc-block-remove').on('click', function() {
            manageScheduleState.blocks.splice(i, 1);
            renderManageScheduleBlocks();
        });
    });
}

function addManageScheduleBlockRow() {
    manageScheduleState.blocks.push({ block_date: '', start_time: '', end_time: '', note: '' });
    renderManageScheduleBlocks();
}

$(document).on('change', '#manageScheduleBranch', function() {
    var facilityId = $(this).val();
    manageScheduleState.facilityId = facilityId ? parseInt(facilityId, 10) : null;
    manageScheduleState.blocks = [];
    manageScheduleState.segments = [];
    renderManageScheduleSegments();

    if (!manageScheduleState.facilityId) {
        $('#manageScheduleBlockedSection').hide();
        $('#manageScheduleNoBranch').show();
        return;
    }

    $('#manageScheduleNoBranch').hide();
    $('#manageScheduleBlockedSection').show();
    loadManageScheduleAvailability(manageScheduleState.facilityId);
});

function saveManageSchedule() {
    var errors = [];

    if (!manageScheduleState.facilityId) {
        errors.push('Branch is required.');
    }

    errors = errors.concat(validateManageScheduleSegments());

    manageScheduleState.blocks.forEach(function(block, i) {
        var label = 'Blocked availability row ' + (i + 1) + ': ';
        if (!block.block_date) {
            errors.push(label + 'Date is required.');
        } else if (block.block_date < todayIsoDate()) {
            errors.push(label + 'Date cannot be in the past.');
        }
        if (!block.start_time || !block.end_time) {
            errors.push(label + 'Start and End time are both required.');
        } else if (timeStrToMinutes(block.end_time) <= timeStrToMinutes(block.start_time)) {
            errors.push(label + 'End time must be later than Start time.');
        }
    });

    if (errors.length) {
        $('#manageScheduleAlert').html(
            '<div class="alert alert-danger"><strong>Unable to save.</strong><ul class="mb-0"><li>' + errors.join('</li><li>') + '</li></ul></div>'
        );
        return;
    }

    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{ route('ccrAvailability.save') }}",
        type: 'post',
        dataType: 'json',
        data: {
            _token: _token,
            facility_id: manageScheduleState.facilityId,
            blocks: manageScheduleState.blocks,
            segments: manageScheduleState.segments
        },
        success: function(response) {
            if (response.success == 1) {
                $('#manageScheduleModal').modal('hide');
                getWeekEvents($("#date").val(), $("#ipeaCalendarSearch").attr('data-value'), function() {});
            } else {
                $('#manageScheduleAlert').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        },
        error: function(xhr) {
            var message = 'Something went wrong, please try again.';
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
            }
            $('#manageScheduleAlert').html('<div class="alert alert-danger">' + message + '</div>');
        }
    });
}
</script>
