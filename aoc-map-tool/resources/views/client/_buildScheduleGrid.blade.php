<?php
    use App\Helpers\H;
    use App\Helpers\LD;
  // var_dump($nthschedule);
?>

<style>
    .highlighted-row {
        background-color: #ADD8E6;
    }
</style>

<div class="main-container nthschedule col-md-12">
    <div class="heading">
        <h3 class="pt-2 pb-2">NTH Schedule</h3>
    <div>
    
    <div class="grid-container col-md-12 p-0">
        <table class="table bg-white">
            <tr>
                <th><input type="checkbox" name="selectall" id="selectall"/></th>
                <th>Day</th>
                @if (LD::checkFeatureFlag('disply-accept-intake-coverage-checkbox-on-add-nth-from'))
                    @if(count($nthschedule) > 0)
                        @if($nthschedule[0]->is_intake == 1 || $readmit == 'true')
                            <th style="text-align:center;">Accepts Intake Coverage</th>
                        @endif
                    @endif
                @else
                    @if(count($nthschedule) > 0)
                        @if($nthschedule[0]->is_intake == 1)
                            <th style="text-align:center;">Accepts Intake Coverage</th>
                        @endif
                    @endif
                @endif
                <th>Start Time</th>
                <th>End Time</th>
                <th>Hours</th>
                @if(LD::checkFeatureFlag('add-pay-range-fields-nth-form'))
                @canPermission('nthFormFieldsForNthCard')
                <th>Flexible</th>
                @endcanPermission
                @endif
                <th>Action</th>
            </tr>

            <?php foreach($nthschedule as $schedule) { ?>
            <tr>
                <td><input data-day='<?php echo $schedule->StartDayOfWeek?>' onclick="enableDisableScheduleButtons()" type="checkbox" name="schedule_id[]" id="schedule_ids" value="<?php echo $schedule->id?>"></td>
                <td class="moon"><?php echo $schedule->StartDayOfWeek?>
                    <?php if($schedule->ShiftTypeDescription=='Weekend Nights (PM-AM)' || $schedule->ShiftTypeDescription=='Weekday Nights (PM-AM)') { ?> 
                        &nbsp;&nbsp;<i class="fa fa-moon-o" aria-hidden="true"></i>
                    <?php } ?>
                </td>
                @if (LD::checkFeatureFlag('disply-accept-intake-coverage-checkbox-on-add-nth-from'))
                    @if($schedule->is_intake == 1 || $readmit == 'true')
                    <td style="text-align:center;"><label class="custom-checkbox"><input type="checkbox" name="AcceptsCoverage" id="AcceptsCoverage" value="1" onclick="acceptsCoverage(this, {{$schedule->id}})" {{($schedule->AcceptsCoverage == 1) ? 'checked' : ''}}><span></span></label></td>
                    @endif
                @else
                    @if($schedule->is_intake == 1)
                    <td style="text-align:center;"><label class="custom-checkbox"><input type="checkbox" name="AcceptsCoverage" id="AcceptsCoverage" value="1" onclick="acceptsCoverage(this, {{$schedule->id}})" {{($schedule->AcceptsCoverage == 1) ? 'checked' : ''}}><span></span></label></td>
                    @endif
                @endif
                <td><?php echo H::getHelper('ConstantHelper')->getSmallTimeFormat($schedule->StartHour.':'.$schedule->StartMinute)?></td>
                <td><?php echo H::getHelper('ConstantHelper')->getSmallTimeFormat($schedule->EndHour.':'.$schedule->EndMinute)?></td>
                <td class="buildschedule-grid-hours"><?php echo $schedule->ShiftHours?></td>
                @if(LD::checkFeatureFlag('add-pay-range-fields-nth-form'))
                @canPermission('nthFormFieldsForNthCard')
                    <td>
                        @if($schedule->ScheduleType == 1)
                        Yes
                        @else
                        No
                        @endif
                    </td>
                @endcanPermission
                @endif
                <td><a href="javascript:void(0)" data-obj='<?php echo json_encode($schedule)?>' onclick="fillBuildScheduleGridItem(this,'edit')" id="edit-schedule"><i class="fa fa-edit" title="Edit"></i></a> 
                    <a href="javascript:void(0);" data-obj='<?php echo json_encode($schedule)?>' onclick="fillBuildScheduleGridItem(this,'remove')" data-id="<?php echo $schedule->id?>" id="delete-schedule"><i class="fa fa-trash-o" title="Delete"></i></a></td>
            </tr>
            <?php }?>

        </table>
    </div>

    <div class="button-container col-md-12 p-0 mb-4" id="build-schedule-bottom-button">
        <div class="col-md-6 text-left p-0 m-0 pull-left ">
            <div class="massbtn"  style="display:none;">
                <button type="button" class="btn btn-primary add buildschedulegrideditall" onclick="massUpdate('editall')">Edit All</button> &nbsp;
                <button type="button" class="btn btn-primary add buildschedulegridremoveall" onclick="massUpdate('removeall')">Remove All</button>&nbsp;
                <button type="button" class="btn btn-primary add buildschedulegridremoveall" onclick="fillBuildScheduleGridItem('{}','add')">Reset</button>
            </div>
        </div>
        <div class="col-md-6  pull-right text-right p-0">
            <button type="button" class="btn btn-primary add buildschedulegridclose" onclick="closeBuildScheduleGrid()">Close</button>
        </div>
        
    </div>
</div>
