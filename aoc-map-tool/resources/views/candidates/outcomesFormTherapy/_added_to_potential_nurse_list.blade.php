<?php
    use App\Helpers\LD;
?>
<style>
    .added-potential-nurse-form-cnt .multiselect-container.dropdown-menu.show{
        width: 100%;
    }
    .added-potential-nurse-form-cnt .btn-group{
        display: block;
    }
    textarea.error,.added-potential-nurse-form-cnt .error + .btn-group{
        border: 1px dashed red;
        color: red;
        font-weight: 300;
    }
    input[type=radio].error{
        outline: 1px solid red
    }
</style>
<div class="row pt-5 added-potential-nurse-form-cnt">
    <div class="col-md-6 pb-3">
        <label for="Nurse_First_Name">First Name</label>
        <input type="input" class="form-control" id="Nurse_First_Name" name="Nurse_First_Name" value="{{$candidatesDetails->firstName}}" />
    </div>
    <div class="col-md-6 pb-3">
        <label for="Nurse_Last_Name">Last Name</label>
        <input type="input" class="form-control" id="Nurse_Last_Name" name="Nurse_Last_Name" value="{{$candidatesDetails->lastName}}" />
    </div>
    <div class="col-md-6 pb-3">
        <label for="Discipline">Discipline</label>
        <select name="Discipline" id="Discipline" class="form-control">
        <option value="">-- Select One --</option>
            @if(LD::checkFeatureFlag('change-att-to-aide-in-add-clinician-form'))
                @foreach(config('constant')['newSkills'] as $k => $v)
                    <option value="{{$v}}">{{$v}}</option>
                @endforeach
            @else
                @foreach(config('constant')['skills'] as $k => $v)
                    <option value="{{$v}}">{{$v}}</option>
                @endforeach
            @endif
        </select>
    </div>
    <div class="col-md-6 pb-3">
        <label for="Email_Address">Email</label>
        <input type="input" class="form-control" id="Email_Address" name="Email_Address" value="{{$candidatesDetails->email}}" />
    </div>
    <div class="col-md-6 pb-3">
        <label for="Address">Address</label>
        <input type="input" class="form-control" id="Address" name="Address" value="{{$candidatesDetails->address}}" />
    </div>
    <div class="col-md-6 pb-3">
        <label for="City">City</label>
        <input type="input" class="form-control" id="City" name="City" value="{{$candidatesDetails->city}}" />
    </div>
    <div class="col-md-6 pb-3">
        <label for="State">State</label>
        <select name="State" id="State" class="form-control">
            <option value="">-- Select One --</option>
            @foreach(config('constant')['states'] as $k => $state)
            <option {{isset($candidatesDetails->state) && $candidatesDetails->state == $k ? "selected='selected'" : ""}}  value="{{$k}}">{{$state}}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 pb-3">
        <label for="Zip_Code">Zip Code</label>
        <input type="input" class="form-control" id="Zip_Code" name="Zip_Code" value="{{$candidatesDetails->postalCode}}" />
    </div>
    <div class="col-md-6 pb-3">
        <label for="Cell_Phone">Cell Phone</label>
        <input type="input" class="form-control" id="Cell_Phone" name="Cell_Phone" value="{{$candidatesDetails->mobile != '' ? $candidatesDetails->mobile : $candidatesDetails->homePhone}}" />
    </div>
    @if(LD::checkFeatureFlag('potential-nurse-map-changes'))
    <div class="col-md-6 pb-3 d-flex align-items-center">
        <label class="col-md-3 align-items-center" for="Gender">Gender</label>
        <div class="col-md-4 align-items-center">
            <input type="radio" id="Gender" name="Gender" value="Male" />
            <label for="">Male</label>
        </div>
        <div class="col-md-4 align-items-center">
            <input type="radio" id="Gender" name="Gender" value="Female" />
            <label for="">Female</label>
        </div>
    </div>
    @else
    <div class="col-md-6 pb-3">
        <label for="Desired_Schedule/Shifts">Desired Schedule/Shifts</label>
        <input type="input" class="form-control" id="Desired_Schedule/Shifts" name="Desired_Schedule/Shifts" value="" />
    </div>
    @endif
    @if(LD::checkFeatureFlag('potential-nurse-map-changes'))
    <div class="col-md-6 pb-3 schedulecontainer">
        @php $days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"]; @endphp
        <label for="Desired_Schedule/Shifts">Desired Schedule/Shifts</label>
        <div id="template" style="display:none !important;">
            <div class="col-md-12 p-0 d-flex" id="schedule-row-{0}">
                <div class="col-md-3 p-1">
                    <select  required="true" data-placeholder="Select Day(s)"  class="form-control selectdays" multiple="multiple" id="selectdays-{0}" name="selectdays[{0}][]">
                        <?php foreach($days as $day) { ?>
                            <option value="<?php echo $day?>"><?php echo $day?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4 p-1">
                    <input type="time"  required="true" placeholder="Start Time" class="form-control" id="start_time-{0}" name="start_time[{0}]">
                </div>
                <div class="col-md-4 p-1">
                    <input type="time" required="true"  placeholder="End Time" class="form-control" id="end_time-{0}" name="end_time[{0}]">
                </div>
                <div class="col-md-1 p-1">
                    <a href="javascript:void(0)" onclick="addNewRow()" class="addNewRow" style="line-height: 35px;"><i class="fa fa-plus m-0"></i></a>
                    <a href="javascript:void(0)" onclick="removeRow(this)" class="removeRow" style="line-height: 35px; display:none;"><i class="fa fa-minus m-0"></i></a>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            var countScheduleRow = 1;

            var template = jQuery.validator.format($.trim($("#template").html()));
            function addNewRow() {
                if (countScheduleRow > 1) {
                    var prev = countScheduleRow;
                    prev--;
                    $('#schedule-row-' + (prev)).find('.addNewRow').hide();
                    $('#schedule-row-' + (prev)).find('.removeRow').show();
                }
                $(template(countScheduleRow)).appendTo('.schedulecontainer');
                console.log(countScheduleRow);
                $('#selectdays-' + countScheduleRow).multiselect({
                    includeSelectAllOption: true,
                    buttonWidth: '100%'
                });
                countScheduleRow++;
            }
            addNewRow();

            function removeRow(ele) {
                $(ele).parent().parent().remove();
            }
        </script>
    </div>
    @else
    <div class="col-md-6 pb-3 d-flex align-items-center">
        <label class="col-md-3 align-items-center" for="Gender">Gender</label>
        <div class="col-md-4 align-items-center">
            <input type="radio" id="Gender" name="Gender" value="Male" />
            <label for="">Male</label>
        </div>
        <div class="col-md-4 align-items-center">
            <input type="radio" id="Gender" name="Gender" value="Female" />
            <label for="">Female</label>
        </div>
    </div>
    @endif
    @if(LD::checkFeatureFlag('potential-nurse-map-changes'))
    <div class="col-md-6 d-flex">
        <div class="col-md-6 pb-3 skilset">
            <label for="Skills_Experience">Skills Experience</label>
            <select class="selectpicker" id="Skills_Experience" name="Skills_Experience[]" multiple data-live-search="true">
                @foreach(config('constant')['potentialNurseSkillsExperience'] as $skill)
                <option value="{{$skill}}">{{$skill}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 pb-3 payexpectations">
            <label for="pay_expectations">Pay Expectations</label>
            <input type="number" class="form-control" id="pay_expectations" name="pay_expectations" value="" />
        </div>
    </div>
    @else
    <div class="col-md-6 pb-3 skilset">
        <label for="Skills_Experience">Skills Experience</label>
        <select class="selectpicker" id="Skills_Experience" name="Skills_Experience[]" multiple data-live-search="true">
            @foreach(config('constant')['potentialNurseSkillsExperience'] as $skill)
            <option value="{{$skill}}">{{$skill}}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div class="col-md-6 pb-3">
        <label for="Years_of_Experience">Years of Experience</label>
        <input type="number" class="form-control" id="Years_of_Experience" name="Years_of_Experience" value="" />
    </div>
    <div class="col-md-6 pb-3">
        <label for="Comments">Comments</label>
        <textarea name="Comments" id="Comments" class="form-control"></textarea>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#Skills_Experience').multiselect({
            includeSelectAllOption: true
        });
    });
    validation = {
        onfocusout:false,
        rules: {
            Nurse_First_Name: 'required',
            Nurse_Last_Name: 'required',
            Discipline: 'required',
            Email_Address: {
                required:true,
                email: true
            },
            City: 'required',
            State: 'required',
            Zip_Code: 'required',
            Cell_Phone: 'required',
            'Desired_Schedule/Shifts': 'required',
            Gender: 'required',
            'Skills_Experience[]': {
                required: function(){
                    return !($('.multiselect-option.dropdown-item.active').length > 0);
                }
            },
            Years_of_Experience: 'required',
            Comments:{
                required: function(){
                    return !($("#Comments").val() !== '');
                }
            },
        },
        errorPlacement: function(error, element) {}
    };
</script>
