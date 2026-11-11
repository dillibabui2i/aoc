<x-modal>
    <x-slot name="id">schedule_corrected_modal</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Confirmation</x-slot>
    <x-slot name="extraHeader"></x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row">
            <h5 id="confirmationDialogMessage">
                @if($requestData['bucket'] =='zero_rates')
                Has this zero rate been corrected in KanTime?
                @else
                Has the schedule been corrected in KanTime?
                @endif
            </h5>
        </div>
    </x-slot>
    <x-slot name="footer">
        <button class="btn btn-primary" id="ScheduleCorrectedYesButton"
        onclick="saveAndcloseScheduleCorrected()">Yes</button>
        <button class="btn btn-primary" id="ScheduleCorrectedNoButton"
        onclick="closeScheduleCorrected()">No</button>
    </x-slot>
</x-modal>


<script>
    function saveAndcloseScheduleCorrected() {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('dashboard.saveScheduleCorrected')}}",
            type: 'post',
            data: {
                id: {{$requestData['id']}},
                bucket: '{{$requestData['bucket']}}',
                _token: _token
            },
            success: function(data) {
                $('#schedule_corrected_modal').modal('hide');
                @if($requestData['bucket'] =='zero_rates')
                $('#dataTable-{{$requestData['bucket']}}').DataTable().ajax.reload(null, false);
                @else
                $('#dataTable-clientcare-schedule-built-auth-{{$requestData['bucket']}}').DataTable().ajax.reload(null, false);
                @endif
            }
        });
    }
    function closeScheduleCorrected() {
        $('#schedule_corrected_modal').modal('hide');
    }
</script>


