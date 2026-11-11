<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $actionButtonTypes = ['997','998','999'];
    $jsonEventTypes = ['997','998'];
?>
<style>
.w40 {
    width: 40%;
}
</style>
<x-modal>
    <x-slot name="id">officeTherapyEventDetailsModal</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="title">{{$data['name']}}</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div id="errorBody" class="alert alert-danger" style="display: none"></div>
        <div class="col-md-12 p-0 d-flex">
            @if(in_array($scheduleDetails->type_id,$jsonEventTypes))
            <table class="table no-border">
                @foreach($data as $k => $v)
                @if($k !='name')
                @if($k == 'Attachment')
                <tr>
                    <td>{{$k}}</td>
                    <td><a href="{{ route('schedules.attechmentprivew', [$scheduleDetails->id]) }}" target="_blank">View Attachment</a></td>
                </tr>
                @else
                <tr>
                    <td>{{$k}}</td>
                    <td>{{$v}}</td>
                </tr>
                @endif
                @endif
                @endforeach
            </table>
            @elseif($scheduleDetails->type_id == 999)
            <table class="table no-border">
                <tr><td>Branch</td><td>{{$scheduleDetails->ipea_location_name}}</td></tr>
                <tr><td>Title</td><td>{{$scheduleDetails->title}}</td></tr>
                <tr><td>Appointment Date</td><td>{{$scheduleDetails->appointment_date}}</td></tr>
                <tr><td>Start Time</td><td>{{$scheduleDetails->appointment_time}}</td></tr>
                <tr><td>End Time</td><td>{{$scheduleDetails->appointment_end_time}}</td></tr>
                <tr><td>Description</td><td>{{$scheduleDetails->description}}</td></tr>
            </table>
            @endif
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
        @if(in_array($scheduleDetails->type_id,$actionButtonTypes))
            @if(LD::checkFeatureFlag('edit-ipea-office-therapy'))
                @if($scheduleDetails->type_id == 999 && H::canPermissions('schedules.removeOfficeEvents'))
                    <button type="button" onclick="rescheduleThisIpeaConfirmation('{{ $scheduleDetails->id}}',{{ $scheduleDetails->type_id }})" class="btn btn-primary">Reschedule</button>
                @elseif($scheduleDetails->type_id == 998 && H::canPermissions('schedules.addtherapyipeaevent'))
                    <button type="button" onclick="rescheduleThisIpeaConfirmation('{{ $scheduleDetails->id}}',{{ $scheduleDetails->type_id }})" class="btn btn-primary">Reschedule</button>
                @elseif($scheduleDetails->type_id == 997 && H::canPermissions('schedules.addofficeipeaevent'))
                    <button type="button" onclick="rescheduleThisIpeaConfirmation('{{ $scheduleDetails->id}}',{{ $scheduleDetails->type_id }})" class="btn btn-primary">Reschedule</button>
                @endif
            @endif
            @if(LD::checkFeatureFlag('delete-ipea-office-therapy'))
                @if($scheduleDetails->type_id == 999 && H::canPermissions('schedules.removeOfficeEvents'))
                    <button type="button" onclick="deleteThisIpeaConfirmation(this,'{{ $scheduleDetails->id}}',{{ $scheduleDetails->type_id }})" class="btn btn-primary">Delete</button>
                @elseif($scheduleDetails->type_id == 998 && H::canPermissions('schedules.addtherapyipeaevent'))
                    <button type="button" onclick="deleteThisIpeaConfirmation(this,'{{ $scheduleDetails->id}}',{{ $scheduleDetails->type_id }})" class="btn btn-primary">Delete</button>
                @elseif($scheduleDetails->type_id == 997 && H::canPermissions('schedules.addofficeipeaevent'))
                    <button type="button" onclick="deleteThisIpeaConfirmation(this,'{{ $scheduleDetails->id}}',{{ $scheduleDetails->type_id }})" class="btn btn-primary">Delete</button>
                @endif
            @endif
        @endif
    </x-slot>
</x-modal>
<script>
    function deleteThisIpeaConfirmation(button,scheduleId,type_id) {
        event.preventDefault();
        modalConfirm('Are you sure want to delete this?','Yes','No',function(res, modelDialog){
            if(res){
                removeNeoSchedule(button,scheduleId,type_id);
                $('#confirmationDialogNew').modal('hide');
            }else{
                modelDialog.modal('hide');
                $('body').addClass("modal-open");
            }
        });
    }
    function rescheduleThisIpeaConfirmation(scheduleId,type_id) {
        if(type_id == 997) {
            addOfficeIpeaEvent(scheduleId,'reschedule');
        }else if(type_id == 998) {
            addTherapyIpeaEvent(scheduleId,'reschedule');
        }else if(type_id == 999) {
            addEvents(scheduleId,'reschedule');
        }
        $('#officeTherapyEventDetailsModal').modal('hide');
    }
</script>