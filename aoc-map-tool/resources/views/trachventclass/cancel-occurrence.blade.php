<x-modal>
    <x-slot name="id">cancelOccurrenceModal</x-slot>
    <x-slot name="size">w20</x-slot>
    <x-slot name="title">Cancel Class</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="modal-body" id="modal-cancel-scope-body">
            <p style="font-size:12px;color:#555;margin-bottom:16px;line-height:1.6">
                You are about to cancel <strong id="cancelOccurrenceClassType"></strong> on
                <strong id="cancelOccurrenceClassDate"></strong> at <strong id="cancelOccurrenceClassTime"></strong>.
                This action cannot be undone.
            </p>
            <button type="button" class="scope-btn danger" data-scope="occurrence">
                <strong>Cancel this event only</strong>
                <div class="scope-desc">Only this occurrence will be cancelled. Future events in this series continue as scheduled.</div>
            </button>
            <button type="button" class="scope-btn danger" data-scope="future">
                <strong>Cancel this and all future events</strong>
                <div class="scope-desc">This occurrence and all upcoming ones in the series will be cancelled. Past events are unaffected.</div>
            </button>
            <button type="button" class="scope-btn danger" data-scope="series">
                <strong>Cancel all events in the series</strong>
                <div class="scope-desc">Every occurrence in this recurring series will be cancelled, including past dates.</div>
            </button>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>

<script>
var CANCEL_OCCURRENCE_ROUTES = {
    occurrence: "{{ route('trachventclass.destroyOccurrence', ['occurrence' => '__ID__']) }}",
    future: "{{ route('trachventclass.destroyFutureOccurrences', ['occurrence' => '__ID__']) }}",
    series: "{{ route('trachventclass.destroyClassOccurrences', ['occurrence' => '__ID__']) }}"
};

var cancelTrachVentOccurrenceInFlight = false;

function cancelTrachVentOccurrence(occurrenceId, scope, requireConfirm) {
    if (requireConfirm && !confirm('Are you sure you want to cancel this class? This action cannot be undone.')) {
        return;
    }

    if (cancelTrachVentOccurrenceInFlight) {
        return;
    }
    cancelTrachVentOccurrenceInFlight = true;
    $('#cancelOccurrenceModal .scope-btn').prop('disabled', true);

    var _token = $('meta[name="csrf-token"]').attr('content');
    var url = CANCEL_OCCURRENCE_ROUTES[scope].replace('__ID__', occurrenceId);

    $.ajax({
        url: url,
        type: 'DELETE',
        data: { _token: _token },
        success: function(response) {
            $('#cancelOccurrenceModal').modal('hide');
            $('#getTrachVentClassInfoModal').modal('hide');
            alert((response && response.message) || 'Class cancelled successfully.');
        },
        error: function(xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong.';
            alert(message);
        },
        complete: function() {
            cancelTrachVentOccurrenceInFlight = false;
            $('#cancelOccurrenceModal .scope-btn').prop('disabled', false);
        }
    });
}

$(document).off('click.cancelOccurrence').on('click.cancelOccurrence', '.cancel-occurrence-btn', function(e) {
    e.preventDefault();
    var $btn = $(this);
    var occurrenceId = $btn.data('occurrence-id');

    // One-time class: no scope to choose, confirm and delete this occurrence straight away.
    if ($btn.data('is-recurring') !== 1) {
        cancelTrachVentOccurrence(occurrenceId, 'occurrence', true);

        return;
    }

    $('#cancelOccurrenceModal').data('occurrence-id', occurrenceId);
    $('#cancelOccurrenceClassType').text($btn.data('class-type'));
    $('#cancelOccurrenceClassDate').text($btn.data('class-date'));
    $('#cancelOccurrenceClassTime').text($btn.data('class-time'));

    $('#cancelOccurrenceModal').modal('show');
});

$(document).off('click.cancelOccurrenceScope').on('click.cancelOccurrenceScope', '#cancelOccurrenceModal .scope-btn', function() {
    // The scope picker already spelled out the consequence of this choice, so no extra native confirm().
    cancelTrachVentOccurrence($('#cancelOccurrenceModal').data('occurrence-id'), $(this).data('scope'), false);
});

$('#cancelOccurrenceModal').off('hidden.bs.modal.cancelOccurrenceBackdropFix').on('hidden.bs.modal.cancelOccurrenceBackdropFix', function(e) {
    e.stopPropagation();
});
</script>
