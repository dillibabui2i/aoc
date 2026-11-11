<div class="row pt-5">
    <div class="col-md-6">
        <label for="note">Notes</label>
        <textarea class="form-control" id="note" name="note"></textarea>
    </div>
</div>
<script>
    validation = {
        onfocusout:false,
        rules: {
            "note": {
                required: function(element) {
                    return !($("#note").val() !== '');
                }
            }
        },
        errorPlacement: function(error, element) {}
    };
</script>
