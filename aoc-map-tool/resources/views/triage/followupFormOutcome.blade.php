<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $loggedInUser = H::getLoggedInUser();
    $currentDate = date('m/d/Y');
    $currentTime = date('H:i');

?>
<style>
    .followUpTriageCallForm .nav-tabs .nav-item.show .nav-link, .followUpTriageCallForm .nav-tabs .nav-link.active {
        background-color: #2f5baa;
        color: #fff;
    }
    .followUpTriageCallForm .nav-tabs .nav-item.show .nav-link, .followUpTriageCallForm .nav-tabs .nav-link {
        background-color: #ddd;
        color: #000;
    }
    .followUpTriageCallForm li.nav-item {
        margin-right: 10px;
    }
    label#contactWithCaller-error {
        position: absolute;
        width: 200px !important;
        bottom: -35px;
        left: 0px;
    }
    .w30 {
        width:30% !important;
    }
</style>

    <form id="followUpTriageCallForm" class="followUpTriageCallForm">
        <input type="hidden" name="user_id" value="{{$originalCallDetails->user_id}}">
        <input type="hidden" name="aoc_patient_id" value="{{$originalCallDetails->aoc_patient_id}}">
        <input type="hidden" name="triagecall_id" id="triagecall_id" value="{{$originalCallDetails->id}}">
        <input type="hidden" name="followupType" id="followupType" value="<?php echo $followType?>" />
        @if(LD::checkFeatureFlag('edit-triage-call'))
            @if(isset($followUpId))
            <input type="hidden" name="followUpId" id="followUpId" value="<?php echo $followUpId?>" />
            @endif
            @include('triage._followUpDetailsForm',['originalCallDetails'=>$originalCallDetails,'followUpDetails'=>$followUpDetails,'followupData'=>$followupData])
        @else
            @include('triage._followUpDetailsForm',['originalCallDetails'=>$originalCallDetails,'followupData'=>$followupData])
        @endif
    </form>


