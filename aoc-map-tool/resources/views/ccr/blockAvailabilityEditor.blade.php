<x-modal>
    <x-slot name="id">blockEditorModal</x-slot>
    <x-slot name="title">Edit Blocked Availability</x-slot>
    <x-slot name="body">
        <div id="blockEditorAlert"></div>
        <div class="form-group row">
            <div class="col-md-12">
                <label for="blockEditorDate">Date <abbr class="text-danger" title="This is required">*</abbr></label>
                <input type="text" autocomplete="off" class="form-control prc-datepicker" id="blockEditorDate">
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-6">
                <label for="blockEditorStart">Start</label>
                <input type="text" autocomplete="off" class="form-control prc-timepicker" id="blockEditorStart">
            </div>
            <div class="col-md-6">
                <label for="blockEditorEnd">End</label>
                <input type="text" autocomplete="off" class="form-control prc-timepicker" id="blockEditorEnd">
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-12">
                <label for="blockEditorNote">Notes</label>
                <input type="text" class="form-control" id="blockEditorNote">
            </div>
        </div>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-secondary mr-auto" onclick="deleteBlockEditor();">Delete Block</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="saveBlockEditor();">Save</button>
    </x-slot>
</x-modal>

<script>
var BLOCK_AVAILABILITY_URL_BASE = "{{ url('/ccr-availability/block') }}";
var blockEditorState = { id: null, startTime: '', endTime: '' };

$(function() {
    $('#blockEditorDate').datepicker({
        format: 'mm/dd/yyyy',
        startDate: new Date(),
        todayHighlight: true
    });
    $('#blockEditorStart').timepicker(PRC_TIMEPICKER_OPTIONS).on('change', function() {
        applyEndTimeConstraint($('#blockEditorEnd'), $(this).val());
    });
    $('#blockEditorEnd').timepicker(PRC_TIMEPICKER_OPTIONS);
});

function openBlockEditor(cardEl) {
    var $card = $(cardEl);
    $('#blockEditorAlert').html('');
    blockEditorState.id = $card.data('block-id');

    var startTime = toAmPm($card.data('start-time'));

    $('#blockEditorDate').val(toDisplayDate($card.data('block-date'))).datepicker('update');
    $('#blockEditorStart').val(startTime);
    $('#blockEditorEnd').val(toAmPm($card.data('end-time')));
    $('#blockEditorNote').val($card.data('note') || '');
    applyEndTimeConstraint($('#blockEditorEnd'), startTime);

    $('#blockEditorModal').modal('show');
}

function saveBlockEditor() {
    var blockDate = toIsoDate($('#blockEditorDate').val());
    var startTime = $('#blockEditorStart').val();
    var endTime = $('#blockEditorEnd').val();
    var errors = [];

    if (!blockDate) {
        errors.push('Date is required.');
    } else if (blockDate < todayIsoDate()) {
        errors.push('Date cannot be in the past.');
    }
    if (!startTime || !endTime) {
        errors.push('Start and End time are both required.');
    } else if (timeStrToMinutes(endTime) <= timeStrToMinutes(startTime)) {
        errors.push('End time must be later than Start time.');
    }

    if (errors.length) {
        $('#blockEditorAlert').html('<div class="alert alert-danger"><ul class="mb-0"><li>' + errors.join('</li><li>') + '</li></ul></div>');
        return;
    }

    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: BLOCK_AVAILABILITY_URL_BASE + '/' + blockEditorState.id,
        type: 'put',
        dataType: 'json',
        data: {
            _token: _token,
            block_date: blockDate,
            start_time: startTime,
            end_time: endTime,
            note: $('#blockEditorNote').val()
        },
        success: function(response) {
            if (response.success == 1) {
                $('#blockEditorModal').modal('hide');
                getWeekEvents($("#date").val(), $("#ipeaCalendarSearch").attr('data-value'), function() {});
            } else {
                $('#blockEditorAlert').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        },
        error: function(xhr) {
            var message = 'Something went wrong, please try again.';
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
            }
            $('#blockEditorAlert').html('<div class="alert alert-danger">' + message + '</div>');
        }
    });
}

function deleteBlockEditor() {
    modalConfirm(
        'Are you sure you want to delete this block? This time will become available for PRC scheduling immediately once deleted.',
        'Delete',
        'Cancel',
        function(confirmed, dialog) {
            dialog.modal('hide');
            if (!confirmed) {
                return;
            }

            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: BLOCK_AVAILABILITY_URL_BASE + '/' + blockEditorState.id,
                type: 'delete',
                dataType: 'json',
                data: { _token: _token },
                success: function(response) {
                    if (response.success == 1) {
                        $('#blockEditorModal').modal('hide');
                        getWeekEvents($("#date").val(), $("#ipeaCalendarSearch").attr('data-value'), function() {});
                    } else {
                        $('#blockEditorAlert').html('<div class="alert alert-danger">' + response.message + '</div>');
                    }
                },
                error: function() {
                    $('#blockEditorAlert').html('<div class="alert alert-danger">Something went wrong, please try again.</div>');
                }
            });
        }
    );
}
</script>
