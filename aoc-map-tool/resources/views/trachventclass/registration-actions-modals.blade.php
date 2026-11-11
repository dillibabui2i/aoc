<style>
.registration-modal-dialog.w30 { max-width: 480px !important; }
.registration-modal-dialog.w40 { max-width: 700px !important; }
.registration-modal .modal-header { border-bottom: 1px solid #ddd; }
.registration-modal .modal-footer { border-top: 1px solid #ddd; padding-top: 12px; }
.registration-summary { background: #f7f8fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 12px 15px; }
.registration-summary dl { margin-bottom: 0; }
.registration-summary dt { color: #6c757d; font-weight: 600; font-style: normal; }
.registration-note { color: #6c757d; font-size: 13px; }
.reschedule-options { border: 1px solid #dee2e6; border-radius: 4px; max-height: 300px; overflow-y: auto; }
.reschedule-option { display: flex; align-items: center; margin: 0; padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #dee2e6; }
.reschedule-option:last-child { border-bottom: 0; }
.reschedule-option input { margin-right: 15px; }
.reschedule-option .option-text { flex: 1; line-height: 1.3; }
.reschedule-option .option-meta { color: #6c757d; font-size: 13px; }
.reschedule-option .option-count { color: #6c757d; white-space: nowrap; }
.reschedule-option.selected { background: #e8f0fb; }
.reschedule-option.disabled { opacity: .5; cursor: not-allowed; background: #f7f8fa; }
.reschedule-option .option-full { color: #e3342f; font-weight: 600; }
.registration-action-btn { background: #fff; border-radius: 4px; padding: 2px 12px; font-size: 13px; }
.registration-action-btn + .registration-action-btn { margin-left: 4px; }
.registration-action-btn.reschedule-registration-btn { color: #1f5fa8; border-color: #1f5fa8; }
.registration-action-btn.cancel-registration-btn { color: #e3342f; border-color: #e3342f; }
.registration-action-btn:hover { background: #f1f3f5; }
.registration-modal .btn-outline-secondary { background: #fff; color: #212529; border-color: #ced4da; }
.registration-modal #cancelRegistrationConfirmBtn { background: #e3342f; border-color: #e3342f; color: #fff; }
.registration-modal #cancelRegistrationConfirmBtn:hover { background: #c82a26; border-color: #c82a26; }
.registration-modal .btn-outline-secondary:hover { background: #f1f3f5; color: #212529; }
</style>

<x-modal>
    <x-slot name="id">cancelRegistrationModal</x-slot>
    <x-slot name="size">registration-modal-dialog w30</x-slot>
    <x-slot name="title">Cancel clinician's registration?</x-slot>
    <x-slot name="class">registration-modal</x-slot>
    <x-slot name="body">
        <p><span id="cancelRegistrationClinicianName"></span> will be removed from this class and a seat will open up.</p>
        <div class="registration-summary mb-3">
            <dl class="row mb-0">
                <dt class="col-sm-4">Clinician</dt><dd class="col-sm-8 mb-1"><span id="cancelRegistrationClinician"></span> (<span id="cancelRegistrationDiscipline"></span>)</dd>
                <dt class="col-sm-4">Class</dt><dd class="col-sm-8 mb-1">{{ $classDetails->classCategoryName }}</dd>
                <dt class="col-sm-4">When</dt><dd class="col-sm-8 mb-1">{{ $classWhen }}</dd>
                <dt class="col-sm-4">Instructor</dt><dd class="col-sm-8 mb-0">{{ $classDetails->instructorName }}</dd>
            </dl>
        </div>
        @if($classDetails->instructorName !== '')
            <p class="registration-note mb-0">{{ $classDetails->instructorName }} will get an in-app notification.</p>
        @endif
        <div class="alert alert-danger mt-3 mb-0" id="cancelRegistrationError" style="display:none"></div>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Keep registration</button>
        <button type="button" class="btn btn-danger" id="cancelRegistrationConfirmBtn">Cancel registration</button>
    </x-slot>
</x-modal>

<x-modal>
    <x-slot name="id">rescheduleRegistrationModal</x-slot>
    <x-slot name="size">registration-modal-dialog w40</x-slot>
    <x-slot name="title">Reschedule clinician</x-slot>
    <x-slot name="class">registration-modal</x-slot>
    <x-slot name="body">
        <div class="registration-summary mb-3">
            <dl class="row mb-0">
                <dt class="col-sm-3">Clinician</dt><dd class="col-sm-9 mb-1"><span id="rescheduleRegistrationClinician"></span> (<span id="rescheduleRegistrationDiscipline"></span>)</dd>
                <dt class="col-sm-3">Current class</dt>
                <dd class="col-sm-9 mb-0">{{ $classDetails->classCategoryName }}, {{ $classWhen }}@if($classDetails->instructorName !== ''), {{ $classDetails->instructorName }}@endif</dd>
            </dl>
        </div>
        <h6 class="mb-0">Select a new date and time</h6>
        <p class="registration-note mb-2">Showing upcoming {{ $classDetails->classCategoryName }} classes. Classes at capacity can't be selected.</p>
        <div id="rescheduleRegistrationList"></div>
        <div class="alert alert-danger mt-3 mb-0" id="rescheduleRegistrationError" style="display:none"></div>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="rescheduleRegistrationConfirmBtn" disabled>Reschedule clinician</button>
    </x-slot>
</x-modal>

<script>
(function() {
    var occurrenceId = {{ $classDetails->occurrenceId }};
    var registrationId = null;
    var urls = {
        cancel: "{{ route('trachventclass.cancelRegistration', ['occurrence' => '__OCC__', 'registration' => '__REG__']) }}",
        options: "{{ route('trachventclass.rescheduleOptions', ['occurrence' => '__OCC__', 'registration' => '__REG__']) }}",
        reschedule: "{{ route('trachventclass.rescheduleRegistration', ['occurrence' => '__OCC__', 'registration' => '__REG__']) }}"
    };

    function url(name) {
        return urls[name].replace('__OCC__', occurrenceId).replace('__REG__', registrationId);
    }

    function errorMessage(xhr) {
        return (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong.';
    }

    function finish(modalId) {
        $(modalId).modal('hide');
        $('#getTrachVentClassInfoModal').modal('hide');
        // Re-fetching the pop-up shows the updated capacity; closing it refreshes the calendar.
        getTrachVentClassInfo(occurrenceId);
    }

    function loadOptions() {
        $('#rescheduleRegistrationConfirmBtn').prop('disabled', true);
        $('#rescheduleRegistrationList').html('<em>Loading classes...</em>');

        $.ajax({
            url: url('options'),
            type: 'GET',
            success: function(response) {
                var $list = $('#rescheduleRegistrationList').empty();

                if (!response.classes.length) {
                    $list.append($('<em>').text('No upcoming classes of this type are available at this branch.'));
                    return;
                }

                var $options = $('<div class="reschedule-options">').appendTo($list);

                response.classes.forEach(function(item) {
                    var disabled = item.is_full || item.already_registered;
                    var parts = item.class_date.split('/');
                    var day = new Date(parts[2], parts[0] - 1, parts[1]).toLocaleDateString('en-US', { weekday: 'short' });
                    var time = item.class_time + (item.end_time ? ' - ' + item.end_time : '');
                    var $radio = $('<input type="radio" name="rescheduleTarget">').val(item.id).prop('disabled', disabled);
                    var $text = $('<span class="option-text">').append(
                        $('<div>').append($('<strong>').text(day + ' ' + item.class_date), document.createTextNode(', ' + time)),
                        $('<div class="option-meta">').text('Instructor: ' + (item.instructor || 'None'))
                    );
                    var $count = $('<span class="option-count">');
                    if (item.is_full) {
                        $count.append($('<span class="option-full">').text('Full'), ' ');
                    } else if (item.already_registered) {
                        $count.append($('<span class="option-full">').text('Already registered'), ' ');
                    }
                    $count.append(document.createTextNode(item.enrolled_count + '/' + item.capacity));

                    $options.append(
                        $('<label class="reschedule-option">').toggleClass('disabled', disabled).append($radio, $text, $count)
                    );
                });
            },
            error: function(xhr) {
                $('#rescheduleRegistrationList').empty();
                $('#rescheduleRegistrationError').text(errorMessage(xhr)).show();
            }
        });
    }

    $(document).off('click.cancelRegistration').on('click.cancelRegistration', '.cancel-registration-btn', function() {
        registrationId = $(this).data('registration-id');
        $('#cancelRegistrationClinicianName').text($(this).data('clinician-name'));
        $('#cancelRegistrationClinician').text($(this).data('clinician-name'));
        $('#cancelRegistrationDiscipline').text($(this).data('clinician-discipline'));
        $('#cancelRegistrationError').hide();
        $('#cancelRegistrationModal').modal('show');
    });

    $(document).off('click.cancelRegistrationConfirm').on('click.cancelRegistrationConfirm', '#cancelRegistrationConfirmBtn', function() {
        var $btn = $(this).prop('disabled', true);

        $.ajax({
            url: url('cancel'),
            type: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function() {
                finish('#cancelRegistrationModal');
            },
            error: function(xhr) {
                $('#cancelRegistrationError').text(errorMessage(xhr)).show();
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    $(document).off('click.rescheduleRegistration').on('click.rescheduleRegistration', '.reschedule-registration-btn', function() {
        registrationId = $(this).data('registration-id');
        $('#rescheduleRegistrationClinician').text($(this).data('clinician-name'));
        $('#rescheduleRegistrationDiscipline').text($(this).data('clinician-discipline'));
        $('#rescheduleRegistrationError').hide();
        $('#rescheduleRegistrationModal').modal('show');
        loadOptions();
    });

    $(document).off('change.rescheduleTarget').on('change.rescheduleTarget', 'input[name="rescheduleTarget"]', function() {
        $('.reschedule-option').removeClass('selected');
        $(this).closest('.reschedule-option').addClass('selected');
        $('#rescheduleRegistrationConfirmBtn').prop('disabled', false);
    });

    $(document).off('click.rescheduleRegistrationConfirm').on('click.rescheduleRegistrationConfirm', '#rescheduleRegistrationConfirmBtn', function() {
        var targetId = $('input[name="rescheduleTarget"]:checked').val();
        if (!targetId) {
            return;
        }

        var $btn = $(this).prop('disabled', true);
        $('#rescheduleRegistrationError').hide();

        $.ajax({
            url: url('reschedule'),
            type: 'PATCH',
            data: { _token: $('meta[name="csrf-token"]').attr('content'), target_occurrence_id: targetId },
            success: function() {
                finish('#rescheduleRegistrationModal');
            },
            error: function(xhr) {
                $('#rescheduleRegistrationError').text(errorMessage(xhr)).show();
                // 409: the class list is stale (e.g. the last seat was taken), so refresh it.
                if (xhr.status === 409) {
                    loadOptions();
                } else {
                    $btn.prop('disabled', false);
                }
            }
        });
    });

    // x-modal has no tabindex, so Bootstrap can't focus these pop-ups and ESC would reach the class details modal underneath.
    $('#cancelRegistrationModal, #rescheduleRegistrationModal').attr('tabindex', '-1');

    $('#cancelRegistrationModal, #rescheduleRegistrationModal').off('hidden.bs.modal.registrationBackdropFix').on('hidden.bs.modal.registrationBackdropFix', function(e) {
        e.stopPropagation();

        // Bootstrap clears body.modal-open when any modal closes; restore it while the class details modal is still open so the page behind doesn't scroll.
        if ($('#getTrachVentClassInfoModal').hasClass('show')) {
            $('body').addClass('modal-open');
        }
    });
})();
</script>
