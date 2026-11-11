
<div class="row pt-5">
    <div class="col-md-6 pb-3">
        <label for="Nurse_Last_Name">Followup Date</label>
        <input type="input" class="form-control followupdate" id="followupdate" name="followupdate" value="">
    </div>
</div>


<script>
    validation = {
        onfocusout:false,
        rules: {
            "followupdate": {
                required: function(element) {
                    return !($("#followupdate").val() !== '');
                }
            }
        },
        errorPlacement: function(error, element) {}
    };
    $(document).ready(function(){
        $('.followupdate').datetimepicker({
            format: 'm/d/Y H:i:s',
            lang: 'en',
        });
    });
    
    


</script>
