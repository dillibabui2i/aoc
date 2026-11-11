<div class="row pt-5">
    <div class="col-md-6">
        <label for="datepicker">Graduation Date</label>
        <input type="input" class="form-control" id="datepicker" name="GraduationDate" value="" />
    </div>
</div>
<script>
    $('#datepicker').datepicker();
    $('#datepicker').datepicker().bind('click', function() {
        $('#ui-datepicker-div').appendTo($('#datepicker').parent());
    });
    validation = {
        onfocusout:false,
        rules: {
            "GraduationDate": {
                required: function(element) {
                    return !($("#datepicker").val() != '');
                }
            }
        },
        messages: {
            datepicker: "Please fill the date",
        },
        errorPlacement: function(error, element) {}
    };
</script>
