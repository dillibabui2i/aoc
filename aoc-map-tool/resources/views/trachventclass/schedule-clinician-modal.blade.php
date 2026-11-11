<x-modal>
    <x-slot name="id">scheduleClinicianModal</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="title">Schedule a Clinician</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <form id="scheduleClinicianForm">
            <input type="hidden" id="scheduleClinicianTrachVentClassId" value="{{ $classDetails->occurrenceId }}">
            <input type="hidden" id="scheduleClinicianProviderId" name="provider_id" value="">
            <input type="hidden" id="scheduleClinicianCandidateId" name="candidate_id" value="">

            <div class="form-group">
                <label>Clinician Type <span class="text-danger">*</span></label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="clinician_type" id="clinicianTypeCurrent" value="current" checked>
                    <label class="form-check-label" for="clinicianTypeCurrent">Current</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="clinician_type" id="clinicianTypeNew" value="new">
                    <label class="form-check-label" for="clinicianTypeNew">New</label>
                </div>
            </div>

            <div class="form-group">
                <label for="scheduleClinicianName">Clinician <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="scheduleClinicianName" autocomplete="off" placeholder="Search current clinicians...">
                <div class="invalid-feedback" id="scheduleClinicianNameError" style="display:none">This field is required.</div>
            </div>

            <div class="form-group">
                <label for="scheduleClinicianDiscipline">Discipline</label>
                <input type="text" class="form-control" id="scheduleClinicianDiscipline" name="discipline" placeholder="Auto-populated">
            </div>

            <div class="form-group">
                <label for="scheduleClinicianPhone">Phone Number</label>
                <input type="text" class="form-control" id="scheduleClinicianPhone" name="phone_number" placeholder="Auto-populated">
            </div>

            <div class="form-group">
                <label for="scheduleClinicianClient">Client</label>
                <input type="text" class="form-control" id="scheduleClinicianClient" autocomplete="off" placeholder="Search active, transferred, on-hold, or intake clients...">
                <input type="hidden" id="scheduleClinicianClientId" name="client_id" value="">
            </div>

            <div class="form-group">
                <label for="scheduleClinicianStartDate">Start Date</label>
                <input type="text" class="form-control" id="scheduleClinicianStartDate" placeholder="mm/dd/yyyy" autocomplete="off">
            </div>

            <div class="alert alert-danger" id="scheduleClinicianFormError" style="display:none"></div>
        </form>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="scheduleClinicianSubmitBtn">Schedule Clinician</button>
    </x-slot>
</x-modal>

<script>
$(document).off('click.scheduleClinician').on('click.scheduleClinician', 'a[data-trach-vent-class-id]', function(e) {
    e.preventDefault();
    var occurrenceId = $(this).attr('data-trach-vent-class-id');
    $('#scheduleClinicianTrachVentClassId').val(occurrenceId);
    $('#scheduleClinicianForm')[0].reset();
    $('#scheduleClinicianProviderId').val('');
    $('#scheduleClinicianCandidateId').val('');
    $('#scheduleClinicianClientId').val('');
    $('#scheduleClinicianName').removeClass('is-invalid');
    $('#scheduleClinicianNameError').hide();
    $('#scheduleClinicianFormError').hide();
    $('#scheduleClinicianName').attr('placeholder', 'Search current clinicians...');
    $('#scheduleClinicianModal').modal('show');
});

$('#scheduleClinicianStartDate').datepicker({ dateFormat: 'mm/dd/yy' });

// Clinician Type toggle — clears the previously selected Clinician/Discipline/
// Phone so those fields never reflect a record from the other pool.
$(document).off('change.scheduleClinician').on('change.scheduleClinician', 'input[name="clinician_type"]', function() {
    var isNew = $(this).val() === 'new';
    $('#scheduleClinicianName').val('');
    $('#scheduleClinicianDiscipline').val('');
    $('#scheduleClinicianPhone').val('');
    $('#scheduleClinicianProviderId').val('');
    $('#scheduleClinicianCandidateId').val('');
    $('#scheduleClinicianName').attr('placeholder', isNew ? 'Search candidates...' : 'Search current clinicians...');
});

$('#scheduleClinicianName').autocomplete({
    source: function(request, response) {
        $.getJSON("{{ route('getCandidateName') }}", {
            status: $('input[name="clinician_type"]:checked').val(),
            term: request.term
        }, response);
    },
    minLength: 2,
    select: function(event, ui) {
        $('#scheduleClinicianDiscipline').val(ui.item.title);
        $('#scheduleClinicianPhone').val(ui.item.phone);
        if ($('input[name="clinician_type"]:checked').val() === 'new') {
            $('#scheduleClinicianCandidateId').val(ui.item.id);
            $('#scheduleClinicianProviderId').val('');
        } else {
            $('#scheduleClinicianProviderId').val(ui.item.id);
            $('#scheduleClinicianCandidateId').val('');
        }
        $('#scheduleClinicianName').removeClass('is-invalid');
        $('#scheduleClinicianNameError').hide();
    },
    change: function(event, ui) {
        if (!ui.item) {
            $('#scheduleClinicianProviderId').val('');
            $('#scheduleClinicianCandidateId').val('');
        }
    }
});

$('#scheduleClinicianClient').autocomplete({
    source: function(request, response) {
        $.ajax({
            url: "{{ route('trachventclass.searchClients') }}",
            data: { q: request.term },
            success: function(data) {
                response(data);
            }
        });
    },
    minLength: 1,
    select: function(event, ui) {
        $('#scheduleClinicianClientId').val(ui.item.id);
    },
    change: function(event, ui) {
        if (!ui.item) {
            $('#scheduleClinicianClientId').val('');
        }
    }
});

$(document).off('click.scheduleClinicianSubmit').on('click.scheduleClinicianSubmit', '#scheduleClinicianSubmitBtn', function() {
    var clinicianType = $('input[name="clinician_type"]:checked').val();
    var providerId = $('#scheduleClinicianProviderId').val();
    var candidateId = $('#scheduleClinicianCandidateId').val();

    if (!providerId && !candidateId) {
        $('#scheduleClinicianName').addClass('is-invalid');
        $('#scheduleClinicianNameError').show();
        return;
    }

    var occurrenceId = $('#scheduleClinicianTrachVentClassId').val();
    var _token = $('meta[name="csrf-token"]').attr('content');

    $('#scheduleClinicianFormError').hide();
    $('#scheduleClinicianSubmitBtn').prop('disabled', true);

    var enrollData = {
        _token: _token,
        clinician_type: clinicianType,
        discipline: $('#scheduleClinicianDiscipline').val(),
        phone_number: $('#scheduleClinicianPhone').val(),
        start_date: $('#scheduleClinicianStartDate').val() || null
    };
    [
        ['provider_id', providerId],
        ['candidate_id', candidateId],
        ['client_id', $('#scheduleClinicianClientId').val()]
    ].forEach(function(pair) {
        if (pair[1]) {
            enrollData[pair[0]] = pair[1];
        }
    });

    $.ajax({
        url: "{{ route('trachventclass.enroll', ['occurrence' => '__ID__']) }}".replace('__ID__', occurrenceId),
        type: 'POST',
        data: enrollData,
        success: function() {
            $('#scheduleClinicianModal').modal('hide');
            $('#getTrachVentClassInfoModal').modal('hide');
            getTrachVentClassInfo(occurrenceId);
        },
        error: function(xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong.';
            $('#scheduleClinicianFormError').text(message).show();
        },
        complete: function() {
            $('#scheduleClinicianSubmitBtn').prop('disabled', false);
        }
    });
});

$('#scheduleClinicianModal').off('hidden.bs.modal.scheduleClinicianBackdropFix').on('hidden.bs.modal.scheduleClinicianBackdropFix', function(e) {
    e.stopPropagation();
});
</script>
