<?php
use App\Helpers\H;
use App\Helpers\LD;
$mediaConsent = 'Not on file';
if(strpos($clientDetails->PreferenceList,'Media Consent Form on File')!==false) {
    $mediaConsent = 'On File';
}
$theSaleLength = '';
if(strlen($clientDetails->CF_TheSale)<255) {
    $theSaleLength = ' (' . strlen($clientDetails->CF_TheSale) . ' characters) ';
}
?>
<?php if(!empty($needToHireChecklistDetails)) { ?>
<section class="nthrecords-section">
    <div class="col-md-12 p-0 nthrecords-log">
        <div class="nthrecords-container col-md-12">
            <div class="tracker-section  col-md-12 clear p-0   pt-3 m-0">
                <div class="schedule-container clear m-0">
                    <div class="comment-heading">
                        <div class="nthrecords-comment-title col-md-3 p-0 pull-left">NTH Record&nbsp;<a href="javascript:void(0)" @if($needToHireChecklistDetails->IsActive != 0) onclick="addNTHForm('{{$needToHireChecklistDetails->needToHireId}}')" @else class='disabled' @endif><i class="fa fa-edit ml-2" aria-hidden="true"></i></a>@if($needToHireChecklistDetails->IsActive != 1) <span class="prioritycolor">CLOSED</span> @endif</div>
                        <div class="comment-time nthrecords-datetime-history col-md-9 p-0 pull-left">
                            Date Added: {{H::getFormatedDate($needToHireChecklistDetails->created_at)}}&nbsp;|&nbsp;Days on NTH: {{$needToHireChecklistDetails->daysInNTH}}&nbsp;|&nbsp;Created Reason:{{$needToHireChecklistDetails->CreatedReason}}
                        </div>
                    </div>
                    <div class="schedule-box clear m-0">
                        <div class="nthschedule-content-container">
                            <div class="row">
                                <div class="col-md-6">
                                    @if($needToHireChecklistDetails->Status != '')
                                    <div class="row">
                                        <div class="col-md-12"><span class="tag">{{$needToHireChecklistDetails->Status}}</span></div>
                                    </div>
                                    @endif
                                    <div class="row">
                                        <div class="col-md-12">Service Needed: {{$needToHireChecklistDetails->ServicesNeeded}}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">Schedule Description: {{$needToHireChecklistDetails->ScheduleText}}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">Skills Needed: {{$needToHireChecklistDetails->skills}}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">Media Consent: {{$mediaConsent}}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">Last Social Media Post:</div>
                                    </div>
                                    @if(LD::checkFeatureFlag('display-frequency-on-client-profile'))
                                    <div class="row">
                                        @if(!empty($clientDetails->ccc_nth_call_frequency))
                                        <div class="col-md-12">NTH Call Frequency:
                                         {{$clientDetails->ccc_nth_call_frequency?
                                         config('constant.nthCallFrequency')[$clientDetails->ccc_nth_call_frequency]
                                         : ''}}</div>
                                        @endif
                                    </div>
                                @endif
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-12">Client Preferences<br> {!! $clientDetails->clientPreference !!}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">Clinician Preferences<br> {!! $clientDetails->clinicianPreference !!}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<div>&nbsp;</div>
<section class="nthrecords-section">
    <div class="col-md-12 p-0 nthrecords-log">
        <div class="nthrecords-container col-md-12">
            <div class="tracker-section  col-md-12 clear p-0   pt-3 m-0">
                <div class="schedule-container clear m-0">
                    <div class="row">
                        <div class="col-md-9">NTH Schedule <?php echo ($totalShiftHours > 0)? '('.$totalShiftHours.' Hours)':''; ?></div>
                        <div class="col-md-3 pb-3"><button type="button" @if($needToHireChecklistDetails->IsActive != 0) onclick="openBuildScheduleForm('{{ $needToHireChecklistDetails->needToHireId }}')" @else disabled @endif class="btn btn-primary">Edit</button>&nbsp;&nbsp;
                        @if(LD::checkFeatureFlag('nth-audit'))
                        <button type="button" class="btn btn-primary" onclick="showAuditDetail();">Audit</button></div>
                        @else
                        <button type="button" class="btn btn-primary">Audit</button></div>
                        @endif
                    </div>
                    <?php if (count($nthScheduleTable) > 0) { ?>
                    <div class="schedule-box clear m-0">
                        <div class="nthschedule-content-container">
                            <table class="table bg-white">
                                <tr>
                                    <th>Day</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Hours</th>
                                    <th>Flexible?</th>
                                    <th>Address</th>
                                    <th>Contact Name</th>
                                    <th>Description</th>
                                </tr>
                                <?php foreach ($nthScheduleTable as $schedule) { ?>
                                    <tr>
                                        <td><?php echo $schedule->StartDayOfWeek ?></td>
                                        <td><?php echo H::getHelper('ConstantHelper')->getSmallTimeFormat($schedule->StartHour . ':' . $schedule->StartMinute) ?></td>
                                        <td><?php echo H::getHelper('ConstantHelper')->getSmallTimeFormat($schedule->EndHour . ':' . $schedule->EndMinute) ?></td>
                                        <td><?php echo $schedule->ShiftHours ?></td>
                                        <td></td>
                                        <td><?php echo $schedule->alternate_address ?></td>
                                        <td><?php echo $schedule->contact ?></td>
                                        <td><?php echo $schedule->location_description ?></td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</section>
<hr>
<section class="nthrecords-section">
    <div class="col-md-12 p-0 nthrecords-log">
        <div class="nthrecords-container col-md-12">
            <div class="tracker-section  col-md-12 clear p-0   pt-3 m-0">
                <div class="schedule-container clear m-0">
                    <div class="schedule-box clear m-0">
                        <div class="nthschedule-content-container">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-12"><strong>NTH Notes: </strong> {{$clientDetails->NTHNotes}}</div>
                                        <div class="col-md-12 pt-3"><strong>The Sale: </strong> {{$clientDetails->CF_TheSale}} {{ $theSaleLength }}</div>
                                    </div>
                                </div>
                                @if(LD::checkFeatureFlag('add-manager-comments-to-client-profile'))
                                    <?php if(H::canPermissions(['manager-comments'])): ?>
                                        <div class="col-md-6">
                                            <div class="row">
                                                <div class="col-md-12"><strong>Biography:</strong> {{$clientDetails->Biography}}</div>
                                                <div class="col-md-12 pt-3">
                                                    <strong>Manager Comments: </strong>
                                                    <span class="att-value editable-textarea" data-inputclass="textarea-size"
                                                        data-emptytext='Click to add manager comments' data-type='textarea'
                                                        data-url='{{route("editable")}}' data-pk="{{ $needToHireChecklistDetails->id }}"
                                                        data-name='manager_comments' data-value="{{ $needToHireChecklistDetails->manager_comments }}"
                                                        data-table='needToHireAccounts'>{{$needToHireChecklistDetails->manager_comments}}</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="col-md-6">
                                            <div class="row">
                                                <div class="col-md-12"><strong>Biography:</strong> {{$clientDetails->Biography}}</div>
                                                <div class="col-md-12 pt-3"><strong>Manager Comments: </strong> {{$needToHireChecklistDetails->manager_comments}}</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                @else
                                    <div class="col-md-6"><strong>Biography:</strong> {{$clientDetails->Biography}}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php } else { ?>
<section class="nthrecords-section">
    No details found
</section>
<?php } ?>
