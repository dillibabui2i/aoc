<div class="row pt-5">
    <div class="col-md-12">
    <label for=""><strong>Requested Follow Up</strong></label>
    </div>
    <div class="col-md-6">
        <label for="datepicker">Date</label>
        <input type="input" class="form-control" id="datepicker" name="followUpDate" value="" />
    </div>
    <div class="col-md-6">
        <label for="followUpTime">Time</label>
        <input type="input" class="form-control" id="followUpTime"
            name="followUpTime">
    </div>
    <div class="col-md-12 mt-2">
        <label for="note">Comments</label>
        <textarea class="form-control" id="note" name="note"></textarea>
    </div>
</div>
<script>
    $('#datepicker').datepicker();
    $('#followUpTime').timepicker({
        "step": 15,
        'forceRoundTime': true,
        'minTime': '06:00:00',
        'maxTime': '21:00:00'
    });
    $('#datepicker').datepicker().bind('click', function() {
        $('#ui-datepicker-div').appendTo($('#datepicker').parent());
    });
    validation = {
        onfocusout:false,
        rules: {
            "followUpDate": {
                required: function(element) {
                    return !($("#datepicker").val() != '');
                }
            },
            "followUpTime": {
                required: function(element) {
                    return !($("#followUpTime").val() != '');
                }
            },
            "note": {
                required: function(element) {
                    return !($("#note").val() !== '');
                }
            }
        },
        messages: {
            datepicker: "Please fill the date",
            followUpTime: "Please fill the time",
        },
        errorPlacement: function(error, element) {}
    };
</script>
