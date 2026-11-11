<x-modal>
    <x-slot name="id">schoolSetupCccAcknowledgedFormModal</x-slot>
    <x-slot name="size">w-50</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="isCloseBtn">false</x-slot>
    <x-slot name="title"></x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <input type="hidden" name="school_setup_id" id="school_setup_id" value="{{ $school_setup_id }}" />
        <h5>Please confirm school set up has been completed and can be removed?</h5>
    </x-slot>
    <x-slot name="footer">
        <button class="btn btn-primary submit"
        onclick="saveSchoolSetupCccAcknowledgedForm(event, this);">Yes</button>
        <button type="button" class="btn btn-primary" onclick="closeSchoolSetupCccAcknowledgedFormPopup();">
            No
        </button>
    </x-slot>
</x-modal>
<script type="text/javascript">
    function saveSchoolSetupCccAcknowledgedForm(e, button, isSaveAndNew = false) {
        e.preventDefault();
        
        button.innerHTML = 'Please wait...';
        button.disabled = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        let school_setup_id = $('#schoolSetupCccAcknowledgedFormModal #school_setup_id').val();
        $.ajax({
            url: "{{route('schoolSetup.saveSchoolSetupCcAcknowledgedForm')}}",
            type: 'post',
            data: {
                school_setup_id:school_setup_id,
                _token: _token
            },
            success: function (data) {
                button.disabled = false;
                button.innerHTML = 'Submit';
                closeSchoolSetupCccAcknowledgedFormPopup();
            }
        });
    }

    function closeSchoolSetupCccAcknowledgedFormPopup() {
        $('#schoolSetupCccAcknowledgedFormModal').modal('hide');
    }

    $('#schoolSetupCccAcknowledgedFormModal').on('hidden.bs.modal', function (e) {
        if ($('#dataTable-ccc_pending_school_setups_request').length) {
            $('#dataTable-ccc_pending_school_setups_request').DataTable().ajax.reload();
        }
    });
</script>
