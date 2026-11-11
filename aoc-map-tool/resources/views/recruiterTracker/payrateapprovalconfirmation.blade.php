<x-modal>
    <x-slot name="id">payrate_approval_modal</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Confirmation</x-slot>
    <x-slot name="extraHeader"></x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row">
            <h5 id="confirmationDialogMessage">Confirm approval of ${{number_format($requestData['payrate'],2)}} with GP of {{$requestData['gp']}}% for {{$requestData['candidate']}}</h5>
        </div>
    </x-slot>
    <x-slot name="footer">
        <button class="btn btn-primary" id="PayrateApprovedYesButton" onclick="saveAndclosePayrateApproved()">Yes</button>
        <button class="btn btn-primary" id="PayrateApprovedNoButton" onclick="closePayrateApproved()">No</button>
    </x-slot>
</x-modal>


<script>
    function saveAndclosePayrateApproved() {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('dashboard.savePayrateApproved')}}",
            type: 'post',
            data: {
                id: '{{$requestData['id']}}',
                payrate: '{{$requestData['payrate']}}',
                gp: '{{$requestData['gp']}}',
                candidate: '{{$requestData['candidate']}}',
                _token: _token
            },
            success: function(data) {
                $('#payrate_approval_modal').modal('hide');
                $('#dataTable-PAYRATE_APPROVALS').DataTable().ajax.reload(null, false);
            }
        });
    }
    function closePayrateApproved() {
        $('#payrate_approval_modal').modal('hide');
    }
</script>


