<x-modal>
    <x-slot name="id">noNeedSchoolSetupFormModal</x-slot>
    <x-slot name="size">w-50</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="isCloseBtn">false</x-slot>
    <x-slot name="title"></x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <input type="hidden" name="aoc_patient_id" id="aoc_patient_id" value="{{ $aoc_patient_id }}" />
        <h5>Please confirm client does not need school set up.</h5>
    </x-slot>
    <x-slot name="footer">
        <button class="btn btn-primary submit"
        onclick="saveNoNeedSchoolSetupForm(event, this);">Confirm</button>
        <button type="button" class="btn btn-primary" onclick="closeNoNeedSchoolSetupPopup();">
            Cancel
        </button>
    </x-slot>
</x-modal>
<script type="text/javascript">
    function saveNoNeedSchoolSetupForm(e, button, isSaveAndNew = false) {
        e.preventDefault();
        
        button.innerHTML = 'Please wait...';
        button.disabled = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        let aoc_patient_id = $('#aoc_patient_id').val();
        $.ajax({
            url: "{{route('schoolSetup.saveNoNeedSchoolSetupForm')}}",
            type: 'post',
            data: {
                aoc_patient_id:aoc_patient_id,
                _token: _token
            },
            success: function (data) {
                button.disabled = false;
                button.innerHTML = 'Submit';
                closeNoNeedSchoolSetupPopup();
            }
        });
    }

    function closeNoNeedSchoolSetupPopup() {
        $('#noNeedSchoolSetupFormModal').modal('hide');
    }

    $('#noNeedSchoolSetupFormModal').on('hidden.bs.modal', function (e) {
        if ($('#dataTable-ccc_client_need_school_setup').length) {
            $('#dataTable-ccc_client_need_school_setup').DataTable().ajax.reload();
        }
    });
</script>
