<x-modal>
    <x-slot name="id">exception_addressed_modal</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Confirmation</x-slot>
    <x-slot name="extraHeader"></x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row">
            <h5 id="confirmationDialogMessage">Has this exception been addressed?</h5>
        </div>
    </x-slot>
    <x-slot name="footer">
        <button class="btn btn-primary" id="ExceptionAddressedYesButton" onclick="saveAndcloseExceptionAddressed()">Yes</button>
        <button class="btn btn-primary" id="ExceptionAddressedNoButton" onclick="closeExceptionAddressed()">No</button>
    </x-slot>
</x-modal>


<script>
    function saveAndcloseExceptionAddressed() {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('dashboard.saveexceptionaddressed')}}",
            type: 'post',
            data: {
                id: {{$requestData['id']}},
                bucket: '{{$requestData['bucket']}}',
                _token: _token
            },
            success: function(data) {
                $('#exception_addressed_modal').modal('hide');
                $('#dataTable-clientcare-exception-{{$requestData['bucket']}}').DataTable().ajax.reload(null, false);
            }
        });
    }
    function closeExceptionAddressed() {
        $('#exception_addressed_modal').modal('hide');
    }
</script>


