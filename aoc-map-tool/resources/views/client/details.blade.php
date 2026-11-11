<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $specialized = 'No';
    if(H::getHelper('NTHChecklist')->isSpecialized($clientDetails->CF_Skills)) {
        $specialized = 'Yes';
    }
    $priority = 'No';
    if(isset($needToHireChecklistDetails->IsPriority) && $needToHireChecklistDetails->IsPriority==1) {
        $priority = 'Yes';
    }
    $theSaleLength = '';
    if(strlen($clientDetails->CF_TheSale)<255) {
        $theSaleLength = ' (' . strlen($clientDetails->CF_TheSale) . ' characters)';
    }

    $isSuperior = 0;
    if(strpos(strtolower($clientDetails->Payer),'superior')!==false || strpos(strtolower($clientDetails->RespitePayer),'superior')!==false ) {
        $isSuperior = 1;
    }
    $fday = date("Y-m-01", strtotime('today'));
    $Wfday = H::getMondayFromDate(date('Y-m-d'),0);
    $weeklyId = DB::table('ccc_followup_calls')
                ->where("ccc_followup_calls.aoc_patient_id", $clientDetails->id)
                ->where("ccc_followup_calls.type", 'nth')
                ->where("ccc_followup_calls.week_month_start", $Wfday)
                ->where(function($query) {
                    $query->whereNotIn("ccc_followup_calls.outcome", ['Completed', 'Not Complete'])
                            ->orWhereNull("ccc_followup_calls.outcome");
                })
                ->where(function($query) {
                    $query->where('ccc_followup_calls.attempt_count', '!=', '3')
                            ->orWhereNull("ccc_followup_calls.attempt_count");
                })
                ->value('id');
    $monthlyID = DB::table('ccc_followup_calls')
                ->where("ccc_followup_calls.aoc_patient_id", $clientDetails->id)
                ->where("ccc_followup_calls.type", 'client')
                ->where("ccc_followup_calls.week_month_start", $fday)
                ->where(function($query) {
                    $query->whereNotIn("ccc_followup_calls.outcome", ['Completed', 'Not Complete'])
                            ->orWhereNull("ccc_followup_calls.outcome");
                })
                ->where(function($query) {
                    $query->where('ccc_followup_calls.attempt_count', '!=', '3')
                            ->orWhereNull("ccc_followup_calls.attempt_count");
                })
                ->value('id');
?>
<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<style>
    .calldropdown.active{ display: inline-block; }
    .dropdown-menu li:hover {
        background-color: #a7aeb5;
    }
    .fa-phone {
        color: #2f5baa;
    }
</style>
<x-modal>
    <x-slot name="id">clientDetails</x-slot>
    <x-slot name="size">w90</x-slot>
    <x-slot name="title">Client Profile \ {{$clientDetails->Patient_FirstName}} {{$clientDetails->Patient_LastName}}</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            @if($clientDetails->intake_bucket != '')
            <a data-bs-dismiss="modal" href="javascript:void(0);"
                onclick="event.preventDefault();
                openNotes('{{$clientDetails->id}}', 'client', 'Client Notes Added', 'note','intake_client_profile')">
                <i class="fa  fa-sticky-note-o"></i>
            </a>
            @else
            <a data-bs-dismiss="modal" href="javascript:void(0);"
                onclick="event.preventDefault();openNotes('{{$clientDetails->id}}', 'client', 'Client Notes Added', 'note')"><i
                class="fa  fa-sticky-note-o"></i>
            </a>
            @endif
            <?php if(LD::checkFeatureFlag('dashboard_monthly_client_followups')){ ?>
                @canPermission(['clientcare.getclientcareexceptions'])
                <div class="dropdown" style="display: inline-block;">
                    <span class="icon"><i class="fa fa-phone"></i></span>
                    <ul class="dropdown-menu calldropdown">
                     @if(LD::checkFeatureFlag('ccc-weekly-monthly-form'))
                        @if(!empty($weeklyId))
                            <li><a href="javascript:void(0)" onclick="getWeeklyMonthlyInputCall('<?php echo $weeklyId?>', 'weekly')" style="color: black; text-decoration: none;margin-left: 6px;">Weekly NTH Call</a></li>
                        @endif
                        @if(!empty($monthlyID))
                            <li><a href="javascript:void(0)" onclick="getWeeklyMonthlyInputCall('<?php echo $monthlyID?>', 'monthly')" style="color: black;text-decoration: none;margin-left: 6px;">Monthly Call</a></li>
                        @endif
                     @else
                        @if(isset($needToHireChecklistDetails))
                            <li><a href="javascript:void(0)" onclick="getWeeklyInputCall('<?php echo $needToHireChecklistDetails->needToHireId?>')" style="color: black; text-decoration: none;margin-left: 6px;">Weekly NTH Call</a></li>
                        @endif
                        <li><a href="javascript:void(0)" onclick="getMonthlyInputCall('<?php echo $clientDetails->id?>')" style="color: black;text-decoration: none;margin-left: 6px;">Monthly Call</a></li>
                     @endif
                     @if(LD::checkFeatureFlag('ccc-followup-calls-change-call-frequency'))
                        @canPermission(['can_edit_ccc_call_frequency'])
                            <li>
                                <a
                                    href="javascript:void(0)"
                                    onclick="showChangeCallFrequency()"
                                    style="color: black;text-decoration: none;margin-left: 6px;">Frequency Change</a>
                            </li>
                        @endcanPermission
                     @endif
                    </ul>
                </div>
                @endcanPermission
            <?php } ?>
            <?php if(LD::checkFeatureFlag('client-new-changes')) { ?>
            <?php if(!empty($dischargedRecords)) { ?>
                Admit # <select id="dischargedDropdown" class="plain-select">
                @foreach($dischargedRecords as $dischargedRecordData)
                <option value="{{ $dischargedRecordData->id }}"
                    @if($dischargedRecordData->id == $clientDetails->id ) selected @endif>
                    {{ $loop->iteration }} - {{ $dischargedRecordData->AdmitNo }}
                </option>
                @endforeach
            </select>
            <div id="cover-spin"></div>
            <?php } ?>
            <?php } ?>
        </div>

    </x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-9 tracker-details client-cnt"  id="detailDivPart">
                @include('client.detailsInner')
            </div>
            <div class="col-md-3 d-flex p-0">
                <?php if(LD::checkFeatureFlag('client-profile-upcoming-recruitment-clinician-clickable')) { ?>
                    <x-client-activity-log>
                        <x-slot name="trackerId"></x-slot>
                    </x-client-activity-log>
                <?php } else { ?>
                <x-activity-log>
                    <x-slot name="trackerId"></x-slot>
                </x-activity-log>
                <?php } ?>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
@if(LD::checkFeatureFlag('ccc-followup-calls-change-call-frequency'))
@canPermission(['can_edit_ccc_call_frequency'])
@php
$nthCallFrequency = config('constant.nthCallFrequency');
$clientCallFrequency = config('constant.clientCallFrequency');
@endphp
<x-modal>
    <x-slot name="id">changeCallFrequency</x-slot>
    <x-slot name="size">w-50</x-slot>
    <x-slot name="title">Call Frequency</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <form id="changeCallFrequencyForm" method="post">
            @csrf
            <input type="hidden" name="aoc_patient_id" value="{{ $clientDetails->id }}"/>
            <div class="col-md-12" id="changeCallFrequencyMsg">
            </div>
            <div class="form-group col-md-12">
                <label for="ccc_nth_call_frequency">NTH call frequency</label>
                <select
                    name="ccc_nth_call_frequency"
                    id="ccc_nth_call_frequency"
                    class="form-control">
                    <option value="0">Please Select Frequency</option>
                    @foreach($nthCallFrequency as $val => $text)
                        <option
                            value="{{$val}}"
                            {{$clientDetails->ccc_nth_call_frequency == $val ? 'selected' : ''}}>{{$text}}</option>
                    @endforeach
                </select>
            </div>
            @if(LD::checkFeatureFlag('ccc-monthly-calls-bucket'))
            <div class="form-group col-md-12">
                <label for="ccc_client_call_frequency">Monthly call frequency</label>
                <select
                    name="ccc_client_call_frequency"
                    id="ccc_client_call_frequency"
                    class="form-control">
                    @foreach($clientCallFrequency as $val => $text)
                        <option
                            value="{{$val}}"
                            {{$clientDetails->ccc_client_call_frequency == $val ? 'selected' : ''}}>{{$text}}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </form>
    </x-slot>
    <x-slot name="footer">
        <button
            type="button"
            class="btn btn-primary"
            id="saveCallFrequency"
            onclick="saveChangeCallFrequency(this)">Save</button>
    </x-slot>
</x-modal>
@endcanPermission
@endif
<script>
@if(LD::checkFeatureFlag('nth-audit'))
function showAuditDetail() {
    $('#showAuditDetailPopup').modal('show');
}
$(document).ready(function() {
    $("#nthauditlist").DataTable({
        "order": [],
        "columns": [
            {"data":"ChangedBy","sortable": false},
            {"data":"created_at","sortable": false},
            {"data":"Day","sortable": false},
            {"data":"StartTime","sortable": false},
            {"data":"EndTime","sortable": false},
            {"data":"ShiftHours","sortable": false},
            {"data":"Type","sortable": false},
            {"data":"Reason","sortable": false},
            {"data":"NetChangeInHours","sortable": false}
        ]
    });
    $('.dropdown .icon').click(function() {
        $(this).siblings('.dropdown-menu').toggleClass('active');
    });
});
@endif
</script>
@if(LD::checkFeatureFlag('ccc-followup-calls-change-call-frequency'))
@canPermission(['can_edit_ccc_call_frequency'])
<script type="text/javascript">
function showChangeCallFrequency() {
    $('#changeCallFrequency').modal('show');
}
function saveChangeCallFrequency(button) {
    button.disabled = true;
    $.ajax({
        url: "{{route('client.saveChangeCallFrequency')}}",
        type: 'post',
        data: $('#changeCallFrequencyForm').serialize(),
        success: function(data) {
            button.disabled = false;
            $('#changeCallFrequencyMsg').html(data.message);
            if (data.success) {
                setTimeout(() => {
                    $('#changeCallFrequency').modal('hide');
                    $('#changeCallFrequencyMsg').empty();
                }, 700);
            }
        }
    });
}
</script>
@endcanPermission
@endif
<?php if(LD::checkFeatureFlag('client-new-changes')) { ?>
<script>
    $(document).ready(function(){
      $('#dischargedDropdown').change(function(){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $('#cover-spin').show(0);
        var selectedOption = $(this).val();
        $.ajax({
            url: "{{route('client.getDetails')}}",
            type: 'get',
            dateType:"html",
          data: {
            id: selectedOption,
            refSource: 'discharge',
            _token: _token
         },
          success: function(response){
            $('#detailDivPart').html(response);
            $('#cover-spin').hide(0);
          },
          error: function(xhr, status, error){
            console.error(xhr.responseText);
            $('#cover-spin').hide(0);
          }
        });
      });
    });
</script>
<?php } ?>
<script>
function reloadClientHistory(id, type, destination) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('clienthistory')}}",
        type: 'get',
        data: {
            id: id,
            type: type,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $(destination).html(data);
        }
    });
}

$(document).ready(function() {
    <?php if(LD::checkFeatureFlag('client-profile-upcoming-recruitment-clinician-clickable')) { ?>
    reloadClientHistory('<?php echo $clientDetails->id?>', 'clienthistory', '.client-activity-log');
    <?php } else { ?>
        reloadClientHistory('<?php echo $clientDetails->id?>', 'clienthistory', '.activity-log');
    <?php } ?>
    $('#clientDetails').on('hide.bs.modal', function(event) {
        $('#nthauditlist').DataTable().clear().destroy();
    });
});
</script>

