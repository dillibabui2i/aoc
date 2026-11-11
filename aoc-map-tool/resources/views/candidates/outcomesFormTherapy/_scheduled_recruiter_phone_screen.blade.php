<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">

<input type="hidden" name="isTherapy" value="{{ $isTherapy }}">

<div class="row pt-5 schedule-recruiter-phone-screen-section">
    <div class="col-md-4">
        <label for="date-to-be-scheduled-for-phone-screen">Scheduled Recruiter Phone Screen Date</label>
        <input type="text" class="form-control required-field" autocomplete="off" id="date-to-be-scheduled-for-phone-screen"
            name="date">
    </div>
    <div class="col-md-4">
        <label for="time-to-be-scheduled-for-phone-screen">Scheduled Recruiter Phone Screen Time</label>
        <input type="text" class="form-control required-field" id="time-to-be-scheduled-for-phone-screen"
            name="time">
    </div>
    <div class="col-md-4">
        <label for="scheduled-recruiter-phone-screen-note">Notes</label>
        <textarea name="note" id="scheduled-recruiter-phone-screen-note" placeholder="Add Notes" class="form-control"></textarea>
    </div>
</div>

<div class="row pt-5">
    <div class="col-md-12">
        <p class="msg-error"></p>
    </div>
</div>
<script>
    validation = {
        onfocusout: false,
        rules: {
            "date": {
                required: true
            },
            "time": {
                required: true
            },
        },
        messages: {
            date: "Scheduled Recruiter Phone Screen Date is required",
            time: "Scheduled Recruiter Phone Screen Time is required",
        },
        errorPlacement: function(error, element) {}
    };

    $(function () {
        $('#date-to-be-scheduled-for-phone-screen').datepicker().bind('click', function () {
            $('#ui-datepicker-div').appendTo($('#date-to-be-scheduled-for-phone-screen').parent());
        });

        $('#time-to-be-scheduled-for-phone-screen').timepicker({
            "step": 15,
            'forceRoundTime': true,
            'minTime': '06:00:00',
            'maxTime': '21:00:00'
        });
    });
</script>
