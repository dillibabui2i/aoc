<?php
    use App\Helpers\H;
    $options = json_encode(explode(",",$clinicianDetails->shift_type));
    $days = array_map([H::class,'getFormatedDate'],explode(",",$clinicianDetails->days_available));
?>

<div class="outcome_container">
    <div class="col-md-12 p-0  readytostartstatusfield">
        <div class="form-group row">
            <div class="col-md-6 intakeStaffForm_reason ">
                <label  class="w-100" for="shift_type">Is your preferred shift type still</label>
                <select
                    class="form-control"
                    name="shift_type"
                    id="shift_type"
                    multiple>
                    <option value="Weekday - Days">Weekday - Days</option>
                    <option value="Weekday - Nights">Weekday - Nights</option>
                    <option value="Weekend - Days">Weekend - Days</option>
                    <option value="Weekend - Nights">Weekend - Nights</option>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-6">
                <input type="hidden" name="days_available_hidden" id="days_available_hidden" value="">
                <label  class="w-100" for="days_available1">What are the dates you are available next month</label>
                <div id="activecoverage_days_available"></div>
            </div>
            <div class="col-md-6">
                <div name="employmenttype" id="available_days">
                    <label  class="w-100" for="available_days_selected">Available Days</label>
                </div>
                <div id="available_days_selected">
                    <?php 
                    foreach($days  as $day) {
                        echo $day."</br>";
                    } ?>
                </div>
            </div>
        </div>
    </div>
    
</div>

<script>
//$(document).ready(function(){
    //$('.ui-datepicker-inline').remove();

    //$(document).ready(function(){
            $('#activecoverage_days_available').datepicker({
                multidate: true,
                todayHighlight: true
            });
            $('#activecoverage_days_available').on('changeDate', function() {
                let selectedDates = $('#activecoverage_days_available').datepicker('getFormattedDate');
                $('#days_available_hidden').val(selectedDates);
                $('#available_days_selected').html(selectedDates.split(',').join('</br>'));
               
            });
            var dateString = '<?php echo json_encode($days)?>';
            var dateArray = jQuery.parseJSON(dateString);
            var dateObject = [];
            for(var i=0; i<dateArray.length;i++){
                    dateObject[i] = new Date(dateArray[i]);
            }
            $('#activecoverage_days_available').datepicker("setDates",dateObject);
    //});


    $('#activeCoverageOutcomeForm #shift_type').multiselect({
        includeSelectAllOption: true,
        maxHeight: 135
    });
    var selectOptions = '<?php echo $options?>';
    var options = jQuery.parseJSON(selectOptions); 
    $('#activeCoverageOutcomeForm #shift_type').val(options);
    $("#activeCoverageOutcomeForm #shift_type").multiselect("rebuild");

//});
</script>