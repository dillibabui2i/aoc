<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $followTypeArray = array_map(function ($ar) {return $ar['followupType'];}, $triageFollowUpDetails);
    $titles = ['1D'=>'24 Hour Follow-Up','2D'=>'48 Hour Follow-Up','7D'=>'7 Day Follow-Up','30D'=>'30 Day Follow-Up']
?>
<section class="follow-type-tabs">
    <div class="row">
        <div class="col-md-3 mb-3">
            <ul class="nav nav-pills flex-column" id="myTab" role="tablist">
                <li class="nav-item">
                    <a data-id="{{$data['id']}}" data-triage-type="original" class="active nav-link triage-edit-form <?php echo (empty($followTypeArray)) ? 'active' :'' ?>" data-toggle="tab" href="#original{{$data['id']}}" role="tab" aria-controls="settings">Original</a>
                </li>
                <?php foreach($followTypeArray as $k => $v) {
                    $currentData = array_search($v, array_column($triageFollowUpDetails, 'followupType'));
                    $currentFollowUpDetails = $triageFollowUpDetails[$currentData];
                    ?>
                    <li class="nav-item">
                        <a data-followup-type="{{$v}}" data-id='{{$currentFollowUpDetails["id"]}}' data-triage-type="followup" class="nav-link triage-edit-form" data-toggle="tab" href="#<?php echo 'i-'.$v.$data['id'];?>" role="tab" aria-controls="home"><?php echo $titles[$v];?></a>
                    </li>
                <?php } ?>
            </ul>
        </div>
        <div class="col-md-9 pl-0 mb-3">
            <div class="tab-content">
            </div>
        </div>
</section>
