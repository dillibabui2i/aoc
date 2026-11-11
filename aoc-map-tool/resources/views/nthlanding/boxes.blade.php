    <?php
    if(count($records)>0) {
        foreach($records as $record) { ?>
                <li class="col-md-6 singlebox-container">
                    <div class="row">
                        <div class="col-md-6   bold large">{{$record->Patient_City}},{{$record->State}} {{$record->Patient_Zip}}</div>

                    </div>
                     <div  class="bold med blue">
                        <?php if($record->isFlexibleSchedule) { ?>
                            Flexible Schedule
                        <?php } ?>
                    </div>
                    <div class="">
						<div class="nursing med"><b>Nursing:</b> {{$record->CF_ClinicianDiscipline}}</div>
                    </div>

                    <div class="">
                        <div class="">
                        <?php if(!empty($record->pay_range_rn) && !empty($record->pay_range_lvn)) { ?>
                                <?php if(!empty($record->pay_range_rn)) { ?>
                                    <p><b>RN :</b> <?php echo $record->pay_range_rn ?></p>
                                <?php }
                                    if(!empty($record->pay_range_lvn)) { ?>
                                    <p><b>LVN :</b> <?php echo $record->pay_range_lvn?></p>
                                <?php } ?>
                        <?php } else { ?>
                                <p>Not Provided</p>
                        <?php } ?>
                        </div>
                    </div>

                    <div class="schedule">
                        <span  class="med"><b>Schedule:</b>       <?php
                            $schedules = explode(",",$record->displaySchedule);
                            foreach($schedules as $schedule) { ?>

                                    <?php echo $schedule ?>

                        <?php } ?></span>

                    </div>

                    <div class="">
                        <div>
                            <span class="med envi"><b>Environment:</b>   <?php echo $record->environment?></span>
                            <div class="">

                            </div>
                        </div>
                    </div>

                    <div class="">
                        <div>
                            <span  class="med"><b>Skills:</b>  <?php echo implode(", ",explode(",",$record->CF_Skills))?></span>
                            <div class="">

                            </div>
                        </div>
                    </div>

                    <div class="">
                        <div>
                            <span  class="med"><b>Language:</b> <?php echo $record->CF_NurseLanguage?></span>
                            <div class="">

                            </div>
                        </div>
                    </div>

                     <div class="">
                        <div class="i-btn"><button class="interest-btn {{ $record->isInterested ? 'btn-green' : '' }}" data-nth-id="{{ $record->id }}"
                    data-interested="{{ $record->isInterested??0 }}">
                    {{ $record->isInterested ? 'Interested' : 'I\'m Interested' }}
                </button></div>
                    </div>
                </li>
        <?php }
    } else { ?>
        <li class="col-md-12 singlebox-container" >
            <div>
                <p>No Results</p>
            </div>
        </li>
    <?php } ?>
    </ul>
</div>

