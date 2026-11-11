<?php
    use App\Helpers\H;
    use App\Helpers\LD;
?>

<style>
    .highlighted-row {
        background-color: #ADD8E6;
    }
</style>

<div class="main-container col-md-12">
    <div class="heading">
        <h3 class="pt-2 pb-2">NTH Schedule</h3>
    <div>
    
    <div class="grid-container col-md-12 p-0">
        <table class="table bg-white">
            <tr>
                <th>Day</th>
                @if (LD::checkFeatureFlag('add-acceptscoverage-to-nth-schedule-page'))
                    @if($nthschedule[0]->is_intake == 1)
                        <th style="text-align:center;">Accepts Intake Coverage</th>
                    @endif
                @endif
                <th>Start Time</th>
                <th>End Time</th>
                <th>Hours</th>
            </tr>

            <?php foreach($nthschedule as $schedule) { ?>
            <tr>
                <td class="moon"><?php echo $schedule->StartDayOfWeek?>
                    <?php if(
                        $schedule->ShiftTypeDescription=='Weekend Nights (PM-AM)' ||
                        $schedule->ShiftTypeDescription=='Weekday Nights (PM-AM)'
                    ) { ?> 
                        &nbsp;&nbsp;<i class="fa fa-moon-o" aria-hidden="true"></i>
                    <?php } ?>
                </td>
                @if (LD::checkFeatureFlag('add-acceptscoverage-to-nth-schedule-page'))
                @if($schedule->is_intake == 1)
                <td style="text-align:center;">
                    <label class="custom-checkbox">
                        <input
                            type="checkbox"
                            name="AcceptsCoverage"
                            id="AcceptsCoverage"
                            value="1"
                            onclick="acceptsCoverage(this, {{$schedule->id}})" {{($schedule->AcceptsCoverage == 1) ? 'checked' : ''}}>
                        <span></span>
                    </label>
                </td>
                @endif
                @endif
                <td>
                    <?php echo H::getHelper('ConstantHelper')->getSmallTimeFormat($schedule->StartHour.':'.$schedule->StartMinute)?>
                </td>
                <td>
                    <?php echo H::getHelper('ConstantHelper')->getSmallTimeFormat($schedule->EndHour.':'.$schedule->EndMinute)?>
                </td>
                <td>
                    <?php echo $schedule->ShiftHours?>
                </td>
            </tr>
            <?php }?>           

        </table>
    </div>
</div>
