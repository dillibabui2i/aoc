<?php
    use App\Helpers\H;
    use App\Helpers\LD;
?>

<style>
    .highlighted-row {
        background-color: #ADD8E6;
    }
</style>

    
    
    <div class="grid-container col-md-12 p-0">
        

        <table class="table bg-white">
            <tr>
                <th>Day</th>
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
