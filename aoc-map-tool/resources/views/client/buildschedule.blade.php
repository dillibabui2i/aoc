<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
    //1 = add
    //2= remove
    //3= add/remove
    //4=add/modify
    //5 =modify
    if (LD::checkFeatureFlag('update-category-reason-for-add-new-schedule')) {
        $reasons = config('constant.updatedReasonCategory');
    } else {
        $reasons = config('constant.reasonCategory');
    }

    // override reasons in constant.updatedReasonCategory
    if (LD::checkFeatureFlag('nth-update-reasons-for-schedule-change')) {
        foreach (['2', '5'] as $mode) {
            $reasons['Caregiver'][$mode] = array_values(
                array_diff(
                    $reasons['Caregiver'][$mode],
                    ['Schedule change']
                )
            );

            array_push(
                $reasons['Caregiver'][$mode],
                'Schedule Change (Not Staffed)',
                'Schedule Change (Staffed)'
            );
        }
        sort($reasons['Caregiver'][$mode], SORT_STRING);
    }

?>
<x-modal>
    <x-slot name="id">buildschedulepopup</x-slot>
    <x-slot name="size">wb25</x-slot>
    <x-slot name="title">Add New Schedule</x-slot>
    <x-slot name="class">buildschedulepopup-form</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    <div class="d-flex">
        <div class="schedulepopup-left  col-md-3 pull-left">
            <form id="buildscheduleform">
                <input type="hidden" name="aoc_nthaccounts_id" id="buildSchedule_aoc_nthaccounts_id" value="" />
                <input type="hidden" name="item_id" id="buildSchedule_item_id" value="" />
                <input type="hidden" name="mode" id="buildSchedule_mode" value="add" />
                <input type="hidden" name="removeallschedules" id="removeallschedules" value="0" />
                <div class="form-group row  p-0 m-0 col-md-12">
                    <div class="col-md-12  pb-2">
                        <span>Complete sections below to add schedule.</span>
                    </div>
                    <div class="col-md-12">
                        <select  required="true" data-placeholder="Select Day(s)"  class="form-control" multiple="multiple" id="selectdays" name="selectdays[]">
                            <?php foreach($days as $day) { ?>
                                <option value="<?php echo $day?>"><?php echo $day?></option>
                            <?php } ?>
                        </select>
                    </div>
                    @if(LD::checkFeatureFlag('add-flexible-schedule-checkbox'))
                    @canPermission('nthFormFieldsForNthCard')
                    <div class="col-md-12  pt-2">
                        <input type="checkbox" id="flexibleschedule" name="flexibleschedule" />
                        <label for="flexibleschedule" class="specialcheckboxlabel">Flexible Schedule</label>
                    </div>
                    @endcanPermission
                    @endif
                    <div class="col-md-12">
                        <div class="col-md-6 pull-left p-0 pt-2 pr-1">
                            <input type="time"  required="true" placeholder="Start Time" class="form-control" id="start_time" name="start_time">
                        </div>
                        <div class="col-md-6 pull-left p-0 pt-2">
                            <input type="time" required="true"  placeholder="End Time" class="form-control" id="end_time" name="end_time">
                        </div>
                    </div>
                    <div class="col-md-12 schedule_address" style="display:none">
                        <input type="text"  placeholder="Choose Address" class="form-control" id="schedulebuildform_address" name="address">
                    </div>
                    <div class="col-md-12  pt-2">
                        <select  class="form-control" id="reason_category" name="reason_category">
                            <option value="">Select Category</option>
                            <option value="AOC">AOC</option>
                            <option value="Caregiver">Caregiver</option>
                            <option value="Clinician">Clinician</option>
                        </select>
                    </div>
                    <div class="col-md-12  pt-2">
                        <select  class="form-control" required="true" id="reason" name="reason">

                        </select>
                    </div>

                    <div class="col-md-12 mt-2 text-center">
                        <button type="button" onclick="saveBuildScheduleForm(event)" id="btnbuildschedule" class="btn btn-primary add">Add</button>
                        &nbsp;<button type="button" class="btn btn-primary add cancelbutton" onclick="fillBuildScheduleGridItem('{}','add')">Cancel</button>
                    </div>
                </div>
            </form>
        </div>

        <div id="buildschedule-grid" class="buildschedule-grid col-md-9 pull-left">

        </div>
    </div>

    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<div class="modal fade" id="clientRemovedConfirmation" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 30%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <p>Remove {{$patientName}} from the NTH?</p>
          <div class="input-group mb-3">
            <div class="input-group-prepend">
                <label class="input-group-text" for="select_nth_reason">Select a Reason</label>
            </div>
            <select id="select_nth_reason" name="select_nth_reason" class="custom-select"></select>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="$('#btnbuildschedule').click();" data-dismiss="modal">Remove All</button>
      </div>
    </div>
  </div>
</div>
<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">

<script>

var reasonCategory = '<?php echo json_encode($reasons)?>';
var buildScheduleMode= [1,3,5];

$(document).ready(function() {



    $(document).on("change","#reason_category",function(){
        $("#reason").empty();
        var jsonObject = jQuery.parseJSON(reasonCategory);
        var currentSelection = $(this).val();
       // var hasOptions = false;
       $("#reason").append($('<option></option>').val("").html("Select Reason"));
        $(jsonObject["global"]).each(function (index,val) {
            for(var i=1; i<=5;i++) {
                if(buildScheduleMode.includes(i)) {
                    $(val[i]).each(function(ai,av){
                        //hasOptions=true;
                        $("#reason").append(
                            $('<option></option>').val(av).html(av)
                        );
                    });
                }
            }
        });

        $(jsonObject[currentSelection]).each(function (index,val) {
            for(var i=1; i<=5;i++) {
                if(buildScheduleMode.includes(i)) {

                    $(val[i]).each(function(ai,av){
                        //hasOptions=true;
                        $("#reason").append(
                            $('<option></option>').val(av).html(av)
                        );
                    });
                }
            }
        });
    });
    $("#reason_category").trigger("change");

    $(document).on("change",".scheduleclick",function(){

        if($(this).val()==1) {
            $(".schedule_address").hide();
        } else {
            $(".schedule_address").show();
        }
    });

    $('#selectdays').multiselect({
        includeSelectAllOption: true,
        buttonWidth: '100%'
    });

    $(".caret").css('float', 'right');
    $(".caret").css('margin', '8px 0');

});


function validateConflictSchedule(cb){
    var selectDays = $("#selectdays").val();
    var startTime = $("#start_time").val();
    var endTime = $("#end_time").val();
    var nthaccountId = $("#buildSchedule_aoc_nthaccounts_id").val();
    var itemId = $("#buildSchedule_item_id").val();
    var mode = $("#buildSchedule_mode").val();

    let _token = $('meta[name="csrf-token"]').attr('content');
    if(mode=='remove' || mode=='removeall') {
        cb(0);
    } else {
        if(selectDays.length > 0 && startTime!='' && endTime!='') {
            $.ajax({
                url: "{{route('client.validateschedule')}}",
                type: 'get',
                data: {
                    selectdays: selectDays,
                    nthaccountId:nthaccountId,
                    itemId:itemId,
                    startTime: startTime,
                    endTime: endTime,
                    _token: _token
                },
                success: function(data) {

                    cb(data.isConflict);
                }
            });
        } else {
            cb(0);
        }
    }
}


function saveBuildScheduleForm(e){
    e.preventDefault();

    if($('#buildscheduleform').valid()){
        validateConflictSchedule(function(isConflict){
            if(isConflict) {
                $("#start_time").addClass("error");
                $("#end_time").addClass("error");
                return isConflict;
            } else {
                $("#start_time").removeClass("error");
                $("#end_time").removeClass("error");

                let _token = $('meta[name="csrf-token"]').attr('content');
                var data = {};
                fields = $('#buildscheduleform').serializeArray();
                data['_token'] = _token;
                $.ajax({
                    url: "{{route('client.savebuildscheduleform')}}",
                    type: 'post',
                    data: {
                        d: fields,
                        _token: _token
                    },
                    success: function(data) {
                        var nthaccountId = $("#buildSchedule_aoc_nthaccounts_id").val();
                        var currentMode = $("#buildSchedule_mode").val();
                        showBuildScheduleGrid(nthaccountId,"buildschedule-grid");
                        fillBuildScheduleGridItem(nthaccountId,"add");
                        @if (LD::checkFeatureFlag('clinician-no-longer-on-case-nth-build-schedules'))
                        if(currentMode == 'add') {
                            checkClinicianNoLongerOnCase(nthaccountId)
                        }
                        @endif
                    }
                });
            }
        });
    }
}

@if (LD::checkFeatureFlag('add-acceptscoverage-to-nth-schedule-page'))
function acceptsCoverage(e, schedule_id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    var data = {};
    data['_token'] = _token;
    data['schedule_id'] = schedule_id;
    data['accepts_coverage'] = $(e).is(':checked') ? 1 : 0;

    $.ajax({
        url: "{{route('client.saveAcceptsCoverage')}}",
        type: 'post',
        data: data,
        success: function(data) {
            if (data.success == 0) {
                alert("Error while saving accepts intake coverage information");
                $(e).prop('checked', 0);
            }
        }
    });
}
@endif
function checkClinicianNoLongerOnCase(nthaccountId) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ccc.getClinicianNoLongerOnCaseNthSchedule')}}",
        type: 'get',
        dateType:"html",
        data: {
            aoc_nthaccounts_id: nthaccountId,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCntNew").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCntNew').html(data);
            $('#clinicianNoLongerOnCaseNthScheduleForm').modal('show');
        }
    });
}
</script>
