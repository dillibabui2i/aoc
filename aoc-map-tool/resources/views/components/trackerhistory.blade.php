<?php
use App\Helpers\H;
use App\Helpers\LD;
?>
<div class="tracker-history-container col-md-12">
    <?php foreach($data as $comment) {  ?>
    <div class="tracker-section  col-md-12 clear p-0   pt-3 m-0">
        <?php
        if(isset($comment['type']) && $comment['type']=='email') {
            $email = json_decode($comment['notes'],true);
        ?>

        <div class="comment-container clear m-0">
            <div class="comment-heading ">
                <div class="comment-title col-md-12 p-0 pull-left">
                    Email Sent to <?php echo $email['to']?> on
                    <?php echo H::getHelper('ActivityNotes')->getDateFormatForTrackerHistory($comment['created_at'])?>
                </div>
                <div class="comment-time datetime-history col-md-4 p-0 pull-left">

                </div>
            </div>
        </div>
        <div class="comment-box clear m-0 ">
            <div class="comment-box-heading-container">
                <div class="comment-box-heading-title text-muted  col-md-12 p-0 pull-left">

                </div>
            </div>
            <div class="email-container">
                <div class="comment-box-notes subject text-muted clear m-0">
                    <p> Subject : <?php echo $email['subject']?> </p>
                </div>

                <div class="comment-box-notes toggle-body box text-muted clear m-0">
                    <p> <?php echo substr(strip_tags($email['body']),0,200)?>... </p>
                </div>

                <div class="comment-box-notes attachments box text-muted clear m-0">

                    <?php
                                        if(isset($email['attachments']) && !empty($email['attachments'])) {
                                             echo "<p> Attachments </p>";
                                            foreach($email['attachments'] as $attachment) {

                                                $destinationPath = storage_path() . '/ipeaattachments/'.$attachment;
                                                if(is_file($destinationPath)) {
                                                    $destinationUrl = asset("storage/ipeaattachments/".$attachment);
                                                    ?>
                    <a href="<?php echo $destinationUrl?>" target="_blank"><?php echo $attachment?></a> <br>
                    <?php }
                                            }
                                        }
                                    ?>
                </div>

                <div class="comment-box-notes box  text-muted clear m-0">
                    <p> <a href="javascript:void(0)" onclick="openEmailTemplate('<?php echo $comment['id']?>')">More</a>
                    </p>
                </div>
            </div>
        </div>


        <?php } else if(isset($comment['type']) && $comment['type']=='comment') { ?>
        <div class="comment-container clear m-0">
            <div class="comment-box clear m-0 ">
                <div class="comment-box-heading-container">
                    <div class="comment-box-heading-title text-muted  col-md-8 p-0 pull-left">
                        <?php echo  $comment['name'].' commented';?>
                    </div>
                    <div class="comment-box-heading text-muted datetime-history  col-md-4 p-0 pull-left">
                        <?php echo H::getHelper('ActivityNotes')->getDateFormatForTrackerHistory($comment['created_at'])?>
                    </div>
                </div>
                <div class="comment-box-notes text-muted clear m-0">
                    <?php echo $comment['notes'] ?>
                </div>
            </div>
        </div>
        <?php } else if(isset($comment['type']) && $comment['type']=='schedule')  { ?>
        <div class="schedule-container clear m-0">
            <div class="comment-heading">
                <div class="comment-title col-md-8 p-0 pull-left">
                    <?php if($comment['schedule_type']=='IPEA') { ?>
                    <?php echo $comment['name'].' Scheduled an '.$comment['schedule_type']?>
                    <?php } else { ?>
                    <?php echo $comment['name'].' Scheduled a '.$comment['schedule_type'] ?>
                    <?php } ?>
                </div>
                <div class="comment-time datetime-history col-md-4 p-0 pull-left">
                    <?php echo H::getHelper('ActivityNotes')->getDateFormatForTrackerHistory($comment['created_at'])?>
                </div>
            </div>

            <div class="schedule-box clear m-0">
                <div class="schedule-heading col-md-12 bg-blue p-2 ">
                    <?php echo $comment['schedule_type']?>
                </div>

                <div class="schedule-content-container col-md-12 p-0">
                    <div class="date-time p-3 text-muted">
                        <?php if(isset($comment['appointment_date']) && !empty($comment['appointment_date']) && isset($comment['appointment_time']) && !empty($comment['appointment_time'])) { ?>
                        Date Time:
                        <?php echo H::getHelper('ActivityNotes')->getAppointmentDate($comment['appointment_date']).' '.$comment['appointment_time'] ?>
                        <br />
                        <?php } ?>

                        <?php if($comment['schedule_type']=='IPEA') { ?>
                        IPEA Location: <?php echo $comment['branch'] ?> <br />
                        <?php } ?>
                        <?php if(isset($comment['patient_name'])) { 
                            $label = 'Client';
                            if(isset($comment['app_with'])) {
                                if($comment['app_with']=='C') {
                                    $label = "Candidate";
                                } else if($comment['app_with']=='P') {
                                    $label = 'Provider';
                                }

                            }
                        ?>
                        <?php echo $label?>: <?php echo $comment['patient_name'] ?> <br />
                        <?php } ?>

                        @if(LD::checkFeatureFlag('shift-type-ccr-client-acitivty-notes'))
                            @if($comment['schedule_type'] == 'CCR')
                                Shift Type: <?php echo $comment['shift_type'] ?> <br />
                            @endif
                        @endif

                        <?php if(isset($comment['outcome'])) { ?>
                        Outcome: <?php echo $comment['outcome'] ?> <br />
                        <?php } ?>
                    </div>
                </div>
            </div>

        </div>
        <?php } else if(isset($comment['type']) && $comment['type']=='activity') { ?>
        <div class="comment-container clear m-0">
            <div class="comment-heading ">
                <div class="comment-title col-md-8 p-0 pull-left">
                    <?php echo $comment['notes']; ?>
                </div>
                <div class="comment-time datetime-history col-md-4 p-0 pull-left">
                    <?php echo H::getHelper('ActivityNotes')->getDateFormatForTrackerHistory($comment['created_at'])?>
                </div>
            </div>
            <?php if(isset($comment['misc1']) && $comment['misc1'] == 'TextBlastContent') { ?>
            <div class="schedule-box clear m-0">
                <div class="schedule-content-container col-md-12 p-0">
                    <div class="date-time p-3 text-muted">
                    <?php echo $comment['misc2']??''; ?>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
        <?php } ?> 
    </div>
    <?php } ?>
</div>

<style>
.clear {
    clear: both;
}

.datetime-history {
    text-align: right;
    font-size: 11px;
    font-style: italic;
}

.comment-title {
    font-style: italic;
    font-size: 12px;
}

.tracker-history-container {
    overflow-y: auto;
    height: 750px;
}

.comment-box,
.schedule-content-container {
    background-clip: padding-box;
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    color: #495057;
    display: block;
    font-size: .9rem;
    font-weight: 400;
    line-height: 1.6;
    padding: 0.375rem 0.75rem;
    transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    width: 100%;
    height: auto;
}

.comment-box-notes {
    font-size: 12px;
}

.comment-box-heading-title {
    font-size: 12px;
    font-style: italic;
}
</style>

<script>
$(document).ready(function() {
    $(document).on("click", ".toggle-button", function() {
        $(".toggle-body").toggle();
        if ($(this).text() == "More")
            $(this).text("Less")
        else
            $(this).text("More");

    });
});
</script>