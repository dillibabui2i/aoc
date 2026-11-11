<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">

<x-modal>
    <x-slot name="id">addCCREventsModal</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">Add Events</x-slot>

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
            <form method="POST" id="addEventsForm">
                @csrf
                <input type="hidden" name="type_id" value="996" />
                <input type="hidden" name="aoc_facility_id" value="1" />
                <div class="form-group row">
                    <!-- <div class="col-md-6">
                        <label for=""> Select Branch <abbr class="text-danger" title="This is required">*</abbr> </label>
                        <select required class="form-control" name="aoc_facility_id" id="aoc_facility_id">
                            <option value=""> Select Branch </option>
                            @foreach($branches as $key => $branch)
                                <option value="{{$branch['id']}}">{{$branch['FacilityName']}}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please Select Branch. This field is required</div>
                    </div> -->
                    <div class="col-md-12">
                        <label for=""> Title <abbr class="text-danger" title="This is required">*</abbr> </label>
                        <input type="text" required class="form-control" name="title" id="title" value=""/>
                        <div class="invalid-feedback">Please enter your Event Title. This field is required</div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for=""> Date<abbr class="text-danger" title="This is required">*</abbr> </label>
                        <input type="date" required class="form-control" name="appointment_date" id="appointment_date" value=""/>
                        <div class="invalid-feedback">Please enter your Appointment Date. This field is required</div>
                    </div>
                    <div class="col-md-6 p-0 d-flex">
                        <div class="col-md-6">
                            <label for=""> Start Time<abbr class="text-danger" title="This is required">*</abbr> </label>
                            <select name="appointment_time" class="form-control" id="appointment_time">
                                <option value="">-- Select One --</option>
                                @foreach(config('constant')['ccrTimeSlot'] as $k => $v)
                                    <option value="{{$v}}">{{$v}}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Please enter your Appointment Time. This field is required</div>
                        </div>
                        <div class="col-md-6">
                            <label for=""> End Time<abbr class="text-danger" title="This is required">*</abbr> </label>
                            <select name="appointment_end_time" class="form-control" id="appointment_end_time">
                                <option value="">-- Select One --</option>
                                @foreach(config('constant')['ccrTimeSlot'] as $k => $v)
                                    <option value="{{$v}}">{{$v}}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Please enter your Appointment Time. This field is required</div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-12 text-area">
                        <label for=""> Description </label>
                        <textarea class="form-control" name="description" id="description" cols="80" rows="10"></textarea>
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
$('#addCCREventsModal').on('hidden.bs.modal', function (e) {
    getWeekEvents($("#date").val(),$("#ipeaCalendarSearch").attr('data-value'), function(data) {});
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
            url: "{{route('ccrCalendar.saveEvent')}}",
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
$('#appointment_time').change(function(){
    $('#appointment_end_time').find('option').show();
    $('#appointment_end_time').find('option[value="'+$(this).val()+'"]').hide();
    $('#appointment_end_time').find('option[value="'+$(this).val()+'"]').prevAll().hide();
});
</script>
