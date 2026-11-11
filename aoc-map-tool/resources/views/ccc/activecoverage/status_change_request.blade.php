<?php
    use App\Helpers\H;
    
    $empStatus = H::getHelper('StatusChangeRequests')->getNewClinicianStatus();
    $src_reasonList = config('constant')['statusChangeRequestReason'];
?>
<div class="col-md-12 p-0  statuschangerequest ">
    
    <div class="form-group row">
        <div class="col-md-6">
            <label for="src_candidate_name" class="text-left">Clinician Name</label>
            <input type="text" class="form-control" id="src_candidate_name" name="src_candidate_name" value="<?php echo $clinicianDetails->FirstName.' '.$clinicianDetails->LastName?>" readonly disable>
        </div>
        <div class="col-md-6">
            <label for="src_candidate_id" class="text-left">Clinician ID</label>
            <input type="text" class="form-control" id="src_candidate_id" name="src_candidate_id" value="<?php echo $clinicianDetails->ClinicianID?>" readonly disable>
        </div>
    </div>
    <div class="form-group row">
        <div class="col-md-6">
            <label for="src_branch" class="text-left">Branch</label>
            <input type="text" class="form-control" id="src_branch" name="src_branch" value="<?php echo $clinicianDetails->FacilityName?>" readonly disable>
        </div>
        <div class="col-md-6">
            <label for="src_current_status" class="text-left">Current Status</label>
            <input type="text" class="form-control" id="src_current_status" name="src_current_status" value="<?php echo $clinicianDetails->EmploymentStatus?>" readonly disable>
        </div>
    </div>


    <div class="form-group row">
        <div class="col-md-6">
            <label for="src_requested_status" class="text-left">Requested Status</label>
            <select class="form-control" id="src_requested_status" name="src_requested_status">
                <option value="">-- Select One --</option>
                @foreach ($empStatus as $item)
                    <option value="{{ $item['value'] }}">{{ $item['text'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label for="src_reason" class="text-left">Reason</label>
            <select class="form-control" id="src_reason" name="src_reason">
                <option value="">-- Select One --</option>
                @foreach ($src_reasonList as $item)
                    <option value="{{ $item }}">{{ $item }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="form-group row">
        <div class="col-md-6">
            <label for="src_date_requested" class="text-left">Date Requested</label>
            <input type="text" class="form-control" id="src_date_requested" name="src_date_requested" value="" autocomplete="off">
        </div>
        
        <div class="col-md-6">
            <label class="text-left">Part Time or Full Time?</label>
            <div name="employmenttype">
                <label class="radio-inline"><input type="radio" name="employment_type"
                value="1"> Part Time</label>
                <label class="radio-inline"><input type="radio" name="employment_type"
                value="2"> Full Time</label>
            </div>
        </div>
    </div>
    
</div>

<script>
    $(document).ready(function(){
        
        $('#src_date_requested').datepicker({
            beforeShow: function(input, inst) {
                var calendar = inst.dpDiv;
                setTimeout(function() {
                    calendar.position({
                        my: 'left bottom',
                        at: 'left top',
                        collision: 'none',
                        of: input
                    });
                }, 1);
            }
        });

    });
</script>
