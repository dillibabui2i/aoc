<?php
use App\Helpers\H;
?>
<x-modal>
    <x-slot name="id">getAfterHoursInfoModal</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Details</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div id="afterhoursShiftSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-8 text-right">
                    <button type="button" data-dismiss="modal" aria-label="Close" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div class="row p-0 nurse-info-cnt" id="afterhoursShiftSuccessFormDiv">
            <form method="post" id="afterhours_shift_form" class="scaffold-form" enctype="multipart/form-data" novalidate="novalidate">
            @csrf
            <input type="hidden" id="afterhours_schedule_id" name="afterhours_schedule_id" value="{{ $afterhourDetails['id']??''}}">
            <input type="hidden" id="assigned_user_id" name="assigned_user_id" value="{{ $afterhourDetails['user_id']??0 }}">
            <div class="col-md-12 p-0 tracker-details">
                    <div class="col-md-12 p-0 d-flex">
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Name: </dt>
                            <dd class="col-sm-6 text-left">{{ $afterhourDetails['title'] }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-12 p-0 d-flex">
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Shift Date:</dt>
                            <dd class='col-sm-6 text-left'>
                                {{H::getFormatedDate($afterhourDetails['start_date'])}}
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-12 p-0 d-flex">
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Shift Time:</dt>
                            <dd class='col-sm-6 text-left'>
                            {{H::getFormatedTime($afterhourDetails['start_date'].' '.$afterhourDetails['start_time'])}} - {{H::getFormatedTime($afterhourDetails['end_date'].' '.$afterhourDetails['end_time'])}}
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-12 p-1 d-flex" >
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Bilingual:</dt>
                            <dd class='col-sm-6 text-left'>
                            {{ ($afterhourDetails['user_id'] > 0) ? (($assigneeUserDetail->bilingual == 1) ? 'Yes':'No'):''}}
                            </dd>
                        </dl>
                    </div>
                    <?php if(H::canPermissions('assign.allshifts') || H::canPermissions('assign.shifts')){ ?>
                    <div id="displayAssigneeDiv">
                    <div class="col-md-12 p-0 d-flex" >
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Assigned:</dt>
                            <dd class='col-sm-6 text-left'>
                            {{ ($afterhourDetails['user_id'] > 0) ? $assigneeUserDetail->name:''}}
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-12 p-0 d-flex">
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right"></dt>
                            <dd class='col-sm-6 text-left'><a href="javascript:void(0);" onclick="changeDisplayAssignee('change');">Change Assignee</a></dd>
                        </dl>
                    </div>
                    <div class="col-md-12 p-0 d-flex">
                        <dl class="col-md-12 p-0 d-flex">
                            <dd class='col-sm-8 text-right'><button type="button" data-dismiss="modal" aria-label="Close" class="btn btn-primary">Close</button></dd>
                            @canPermission(['manageafterhoursclass','manageafterhoursallclass','deleteafterhoursclass'])
                            <dt class="col-sm-4 p-0 text-left"><button type="button" onclick="deleteShift()" class="btn btn-primary">Delete</button></dt>
                            @endcanPermission
                        </dl>
                    </div>
                    </div>
                    <div id="changeAssigneeDiv" style="display: none;">
                    <div class="col-md-12 p-1 d-flex">
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">{{ ($afterhourDetails['user_id'] > 0) ? 'Change Assignee' : 'Add Assignee'}}:</dt>
                            <dd class='col-sm-6 text-left'>
                            <select class="form-control" name="user_id" id="user_id">
                                <option value="0" {{ ($afterhourDetails['user_id'] == 0) ? 'selected="selected"' : ''}}>Uncovered</option>
                                @if(count($assignmentUser) > 0 )
                                    @foreach($assignmentUser as $assignmentUserData)
                                        <option value="{{$assignmentUserData['id']}}" {{ ($afterhourDetails['user_id'] > 0 && $assignmentUserData['id'] == $afterhourDetails['user_id']) ? 'selected="selected"' : ''}}>{{ $assignmentUserData['name'] }}{{($assignmentUserData['bilingual'] == 1)?'*':''}}</option>
                                    @endforeach
                                @endif
                            </select>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-12 p-0 d-flex">
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right"></dt>
                            <dd class='col-sm-8 text-left'>
                                <div class="col-md-12 p-0 d-flex">
                                    <div class="form-check">
                                    <input class="form-check-input" type="radio" name="shift_update" id="shiftupdate1" value="only_this">
                                    <label class="form-check-label" for="shiftupdate1">Change this shift</label>
                                    </div>
                                </div>
                                <div class="col-md-12 p-0 d-flex">
                                    <div class="form-check">
                                    <input class="form-check-input" type="radio" name="shift_update" id="shiftupdate2" value="this_and_following">
                                    <label class="form-check-label" for="shiftupdate2">Change this and following shifts</label>
                                    </div>
                                </div>
                                <div class="col-md-12 p-0 d-flex">
                                    <div class="form-check">
                                    <input class="form-check-input" type="radio" name="shift_update" id="shiftupdate3" value="all" >
                                    <label class="form-check-label" for="shiftupdate3">Change all shifts</label>
                                    </div>
                                </div>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-12 p-0" id="showAssigneeLink">
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right"></dt>
                            <dd class='col-sm-6 text-left'><a href="javascript:void(0);" onclick="changeDisplayAssignee('show');">Cancel Changes</a></dd>
                        </dl>
                    </div>
                    <div class="col-md-12 p-0 d-flex">
                        <dl class="col-md-12 p-0 d-flex">
                            <dd class='col-sm-8 text-right'><button type="submit" id="savebtn" class="btn btn-primary">Submit</button></dd>
                            @canPermission(['manageafterhoursclass','manageafterhoursallclass','deleteafterhoursclass'])
                            <dt class="col-sm-4 p-0 text-left"><button type="button" onclick="deleteShift()" class="btn btn-primary">Delete</button></dt>
                            @endcanPermission
                        </dl>
                    </div>
                    </div>
                    <?php } else {?>
                    <div class="col-md-12 p-0 d-flex">
                    <dl class="col-md-12 p-0 d-flex">
                        <dt class="col-sm-4 p-0 text-right">Assigned:</dt>
                        <dd class='col-sm-6 text-left'>
                        {{ ($afterhourDetails['user_id'] > 0) ? $assigneeUserDetail->name:''}}
                        </dd>
                    </dl>
                </div>
                    <?php }?> 
            </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>

<x-modal>
    <x-slot name="id">getShiftDeleteModal</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Confirmation</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div id="deleteShiftSuccessDiv" style="display: none">
            <div id="deleteShiftSuccessBody"></div>
            <div class="row pt-2">
                <div class="col-md-8 text-right">
                    <button type="button" onclick="closeDeleteModal()" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div class="row p-0 nurse-info-cnt" id="deleteShiftSuccessFormDiv">
            <form method="post" id="delete_shift_form" class="scaffold-form" enctype="multipart/form-data" novalidate="novalidate">
            @csrf
            <input type="hidden" id="afterhours_schedule_id" name="afterhours_schedule_id" value="{{ $afterhourDetails['id']??''}}">
            <div class="col-md-12 p-0 tracker-details">
                    <div class="col-md-12 p-0 d-flex">
                        Are you sure you want to delete this shift?
                    </div>
                <div class="col-md-12 p-1 d-flex">
                    <dl class="col-md-12 p-0 d-flex">
                        <dt class="col-sm-4 p-0 text-right"><button type="button" id="yesBtn" onclick="deleteThisShift()" class="btn btn-primary">Yes</button></dt>
                        <dd class='col-sm-6 text-left'><button type="button" data-dismiss="modal" class="btn btn-primary">No</button>
                        </dd>
                    </dl>
                </div>
            </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>

<script type="text/javascript">
    // Wait for the DOM to be ready
    $(function() {
        $("#afterhours_shift_form").validate({
            onfocusout: false,
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
                "user_id": {
                    required: true
                },
                "shift_update": {
                    required:true
                }
            }
        });
    });

    function deleteShift() {
        $('#getShiftDeleteModal').modal('show');
    };
    function closeDeleteModal() {
        $('#getShiftDeleteModal').modal('hide');$('#getAfterHoursInfoModal').modal('hide');
    };

    function deleteThisShift() {
        $('.btn').attr("disabled", true);
        $('#yesbtn').text('Please wait...');
        $.ajax({
            url: "{{ route('afterhours.deleteAfterHoursShift') }}",
            type: "POST",
            data: $("#delete_shift_form").serialize(), //only input
            success: function(response) {
                $('.btn').removeAttr("disabled");
                $('#yesbtn').text('Yes');
                if (response) {
                    if (response.success == 1) {
                        $("#deleteShiftSuccessBody").html(response.message);
                        $("#deleteShiftSuccessFormDiv").hide();
                        $("#deleteShiftSuccessDiv").show();
                        $("#delete_shift_form")[0].reset();
                    } else {
                        $("#deleteShiftSuccessFormDiv").show();
                        $("#deleteShiftSuccessDiv").hide();
                        if (response.success == 0) {
                            console.log(response.message);
                            return false;
                        }
                    }
                }
                getTrachVentWeekEvents($('#states').val(), $('#role_id').val(), $("#date").val(), $("#department_name").val(),$('input[name=type]:checked').val());
            },
            error: function(response) {
                console.log(response.message);return false;
            }
        });
    }

    $("form#afterhours_shift_form").submit(function(e) {
        e.preventDefault();
        if ($("#afterhours_shift_form").valid() == false) {
            return false;
        }
        
        $('#savebtn').attr("disabled", true);
        $('#savebtn').text('Please wait...');
        $.ajax({
            url: "{{ route('afterhours.saveAfterHoursInfo') }}",
            type: "POST",
            data: $("#afterhours_shift_form").serialize(), //only input
            success: function(response) {

                $('#savebtn').removeAttr("disabled");
                $('#savebtn').text('Save');
                if (response) {
                    if (response.success == 1) {
                        $("#successBody").html(response.message);
                        $("#afterhoursShiftSuccessFormDiv").hide();
                        $("#afterhoursShiftSuccessDiv").show();
                        //$("#scheduleNextForm").modal('show');
                        $("#afterhours_shift_form")[0].reset();
                    } else {
                        $("#afterhoursShiftSuccessFormDiv").show();
                        $("#afterhoursShiftSuccessDiv").hide();
                        if (response.success == 0) {
                            console.log(response.message);
                            return false;
                        }
                    }
                }
                getTrachVentWeekEvents($('#states').val(), $('#role_id').val(), $("#date").val(), $("#department_name").val(),$('input[name=type]:checked').val());
            },
            error: function(response) {
                console.log(response.message);return false;
            }
        });
    });
</script>
@if($afterhourDetails['user_id'] > 0)
<script>
    function changeDisplayAssignee(div_section) {
        $("#changeAssigneeDiv").hide();
        $("#displayAssigneeDiv").show();
        if(div_section == 'change') {
            $("#displayAssigneeDiv").hide();
            $("#changeAssigneeDiv").show();
        }
    }
</script>
@else
<script>
    $("#displayAssigneeDiv").hide();
    $("#changeAssigneeDiv").show();
    $("#showAssigneeLink").hide();
</script>
@endif
