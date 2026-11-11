<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $states = H::getHelper("ConstantHelper")->getDashboardState();
    $adsType = config('constant')['adsType'];
    $t = '';
    if(isset($id)){
        $t = $id;
    }elseif(isset($ids)){
        $t = $ids;
    }
    $id = $t;
?>

<x-modal>
    <x-slot name="id">missedvisitsmodal</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Confirmation</x-slot>
    <x-slot name="extraHeader"></x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row">
            <form class="row" action="" id="savemissedvisits">
                <input type="hidden" name="missedvisitsid" id="missedvisitsid" value="{{$id}}">
            </form>
            <h5 id="confirmationDialogMessage">Missed visit has been documented in KanTime?</h5>
        </div>
    </x-slot>
    <x-slot name="footer">
        <button class="btn btn-primary" id="missedvisitsYesButton" onclick="saveAndcloseMissedVisits()">Yes</button>
        <button class="btn btn-primary" id="missedvisitsNoButton" onclick="closeMissedVisits()">No</button>
    </x-slot>
</x-modal>


<script>
    function saveAndcloseMissedVisits() {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('dashboard.setmissedvisitform')}}",
            type: 'post',
            data: {
                id: $("#missedvisitsid").val(),
                _token: _token
            },
            success: function(data) {
                $('#missedvisitsmodal').modal('hide');
                $('#dataTable-clientcare-exception-{{$type}}').DataTable().ajax.reload(null, false);
            }
        });
    }
    function closeMissedVisits() {
        $('#missedvisitsmodal').modal('hide');
    }
</script>


