<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">

<style>
    #candidateShowNextStepForm .modal-content {
        min-height: 900px !important;
    }
</style>
<div class="row pt-5 schedule-clinical-interview-section">
    <div class="col-md-6">
        <label for="clinical-manager-for-interview">Clinical manager to do the interview</label>

        <select class="form-control" id="clinical-manager-for-interview" name="clinicalManager">
            @foreach($clinicalManagers as $clinicalManager)
                <option {{ $selectedClinicalManager == $clinicalManager->id ? 'selected' : '' }} value="{{ $clinicalManager->id }}">{{ $clinicalManager->name }}</option>
            @endforeach
        </select>



    </div>
    <div class="col-md-3">
        <label for="date-to-be-scheduled-for-interview">Date to be scheduled</label>
        <input type="text" class="form-control" id="date-to-be-scheduled-for-interview"
            name="date" value="{{ $date }}">
    </div>
    <div class="col-md-3">
        <label for="time-to-be-scheduled-for-interview">Time to be scheduled</label>
        <input type="text" class="form-control" id="time-to-be-scheduled-for-interview"
            name="time" value="{{ $time }}">
    </div>
</div>

<div class="row pt-5">
    <div class="col-md-12">
        <p class="msg-error"></p>
    </div>
</div>
<script>
    validation = {
        onfocusout:false,
        rules: {
            "clinicalManager": {
                required: true
            },
            "date": {
                required: true
            },
            "time": {
                required: true
            },
        },
        messages: {
            clinicalManager: "Client is require",
            date: "Payer is require",
            time: "Pay_Rate is require",
        },
        errorPlacement: function(error, element) {}
    };
    $(function(){

        $('#date-to-be-scheduled-for-interview').datepicker().bind('click', function () {
            $('#ui-datepicker-div').appendTo($('#date-to-be-scheduled-for-interview').parent());
        });

        $('#time-to-be-scheduled-for-interview').timepicker({
            "step": 15,
            'forceRoundTime': true,
            'minTime': '06:00:00',
            'maxTime': '21:00:00'
        });

        $("#Client").autocomplete({
            source: "{{ route('getScheduleClient') }}",
            minLength: 2,
            select: function(event, ui) {
                $("#Payer").val(ui.item.payer);
            }
        });
    });
</script>
