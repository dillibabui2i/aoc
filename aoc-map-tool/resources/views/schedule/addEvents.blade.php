<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<style>
#eventFormDiv .multiselect-native-select{
    display: block;
    width: 100%;
}
#eventFormDiv .multiselect-native-select .btn-group {
    max-width: 100%;
    width: 100%;
}
</style>
<x-modal>
    <x-slot name="id">addEvents</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">{{ ($mode == 'edit'  || $mode == 'reschedule') ? 'Edit Events' : 'Add Events' }}</x-slot>

    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div id="eventSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" data-dismiss="modal" aria-label="Close" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div id="eventFormDiv">
            <form action="{{route('schedules.saveEvents')}}" method="POST" id="addEventsForm">
                @csrf
                <input type="hidden" name="type_id" value="999" />
                @if($mode == 'edit'  || $mode == 'reschedule')
                    <input type="hidden" name="id" value="{{$data->id}}" />
                @endif
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for=""> Select Branch <abbr class="text-danger" title="This is required">*</abbr> </label>
                        <select required class="form-control" name="ipea_location_id[]" id="ipea_location_id" {{($mode == 'reschedule') ? 'readonly' : ''}} multiple data-live-search="true" multiple>
                            <option value=""> Select Branch </option>
                            @foreach($branches as $key => $branch)
                            <optgroup label="{{$key}}">
                                @foreach($branch as $b)
                                <option
                                    {{(isset($lastCalendarSearch['branch']) && $b['id'] == $lastCalendarSearch['branch']   || (($mode == 'edit'  || $mode == 'reschedule') && $b['id'] == $data->ipea_location_id)) ? 'selected="selected"' : ''}}
                                    value="{{$b['id']}}">{{$b['branch']}}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please Select Branch. This field is required</div>
                    </div>
                    <div class="col-md-6">
                        <label for=""> Title <abbr class="text-danger" title="This is required">*</abbr> </label>
                        <input type="text" required class="form-control" name="title" id="title" value="{{($mode == 'edit'  || $mode == 'reschedule') ? $data->title : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}/>
                        <div class="invalid-feedback">Please enter your Event Title. This field is required</div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for=""> Date<abbr class="text-danger" title="This is required">*</abbr> </label>
                        <input type="date" required class="form-control" name="appointment_date" id="appointment_date" value="{{($mode == 'edit'  || $mode == 'reschedule') ? $data->appointment_date : ''}}"/>
                        <div class="invalid-feedback">Please enter your Appointment Date. This field is required</div>
                    </div>
                    <div class="col-md-6 d-flex p-0">
                        <div class="col-md-6">
                            <label for=""> Start Time<abbr class="text-danger" title="This is required">*</abbr> </label>
                            <input type="text" required class="form-control" name="appointment_time" id="appointment_time" value="{{($mode == 'edit'  || $mode == 'reschedule') ? $data->appointment_time : ''}}"/>
                            <div class="invalid-feedback">Please enter your Appointment Time. This field is required</div>
                        </div>
                        <div class="col-md-6">
                            <label for=""> End Time<abbr class="text-danger" title="This is required">*</abbr> </label>
                            <input type="text" required class="form-control" name="appointment_end_time" id="appointment_end_time" value="{{($mode == 'edit'  || $mode == 'reschedule') ? $data->appointment_end_time : ''}}"/>
                            <div class="invalid-feedback">Please enter your Appointment End Time. This field is required</div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-12 text-area">
                        <label for=""> Description </label>
                        <textarea class="form-control" name="description" id="description" cols="80" rows="10" {{($mode == 'reschedule') ? 'readonly' : ''}}>{{($mode == 'edit'  || $mode == 'reschedule') ? $data->description : ''}}</textarea>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-12">
                        <button class="btn btn-primary" id="addEventsBtn" type="button">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>
<script>
$('#addEvents').on('hidden.bs.modal', function (e) {
    if ($('body.getSchedules #dataTable').length) {
        $('#dataTable').DataTable().ajax.reload();;
    }
});
$("#addEventsBtn").click(function(event) {
    var vForm = $('#addEventsForm');
    event.preventDefault();
    if (vForm[0].checkValidity() === false) {
        event.preventDefault();
        event.stopPropagation();
    } else {
        $('#addEventsBtn').attr("disabled", true);
        $('#addEventsBtn').text('Please wait...');
        $.ajax({
            url: "{{route('schedules.saveEvents')}}",
            type: "POST",
            data: $('#addEventsForm').serialize(),
            success: function(response) {

                $('#addEventsBtn').removeAttr("disabled");
                $('#addEventsBtn').text('Save');
                if (response) {

                    if (response.success == 1) {
                        $("#successBody").html(response.message);
                        $("#eventFormDiv").hide();
                        $("#eventSuccessDiv").show();
                        $("#addEventsForm")[0].reset();
                        @if($mode == 'reschedule')
                        getWeekEvents($('#states').val(), $('#branchSelect').val(), $("#date").val(), $("#ipeaCalendarSearch").attr('data-value'),function(data) {});
                        @endif
                    } else {
                        $("#eventFormDiv").show();
                        $("#eventSuccessDiv").hide();
                    }

                }
            }
        });
    }
    vForm.addClass('was-validated');
});
$(document).ready(function() {
    $('#appointment_time').timepicker({
        "step": 15,
        'forceRoundTime': true,
        'minTime': '08:00:00',
        'maxTime': '19:00:00'
    });
    $('#appointment_end_time').timepicker({
        "step": 15,
        'forceRoundTime': true,
        'minTime': '08:00:00',
        'maxTime': '19:00:00'
    });
    $('#appointment_time').on('change', function() {
        $('#appointment_end_time').val('');
        var selectedHour = getTimeValue($(this).val());
        var endTimepicker = $('#appointment_end_time');
        var updatedOptions = {
            minTime: selectedHour
        };
        endTimepicker.timepicker('option', updatedOptions);
    });

    function getTimeValue(timeString) {
        var [hours, minutes, period] = timeString.match(/\d+|am|pm/g);
        var parsedHour = parseInt(hours);
        var parsedMinute = parseInt(minutes);
        var isPM = period.toLowerCase() === 'pm';
        var hour = isPM && parsedHour < 12 ? parsedHour + 12 : parsedHour;
        var minute = parsedMinute < 10 ? '0' + parsedMinute : parsedMinute;
        return hour + ':' + minute + ':00';
    }
    $(document).ready(function() {
        $('#ipea_location_id').multiselect({
            includeSelectAllOption: true,
        });
    });
});
</script>
