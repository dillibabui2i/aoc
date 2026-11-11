<?php
use App\Helpers\H;
use App\Helpers\LD;

?>


<div class="card">
    <input type="hidden" name="reload-jobvite_requisition_jobviteopen" id="reload-jobvite_requisition_jobviteopen" value="1" />
    <div class="card-header" id="jobvite_requisition_jobviteopen-container">
        <h4 class="mb-0">
            <button class="btn btn-link btn-block text-left collapsed" type="button"
                data-toggle="collapse" data-target="#jobvite_requisition_jobviteopen" aria-expanded="false"
                aria-controls="collapseOne">
                REQUISITIONS TO OPEN <span id="jobvite_requisition_jobviteopen-counter"></span> <i class="fa fa-chevron-right"></i>
            </button>
        </h4>
    </div>

    <div id="jobvite_requisition_jobviteopen" class="collapse " aria-labelledby="dailyactivityone"  data-parent="#jobvite">
        <div class="card-body">
            @if(LD::checkFeatureFlag('recruiting-dashboard-update-descriptions'))
                <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                    <b>Description:</b> Approved or requested positions that are ready for the recruiter to create or
                    open in Jobvite based on current NTH opportunities.<br>
                    <b>Timeline:</b> Review daily and mark completed within 1 business day from the created date.
                    Any requisition to open that is not marked completed after 1 business day will be highlighted in red.</p>
            @endif
        <?php  echo H::getHelper("JobviteRequisition")->getDataTableHtml("jobvite_requisition_jobviteopen")?>
        </div>
    </div>
</div>


<div class="card">
    <input type="hidden" name="reload-jobvite_requisition_jobviteclosed" id="reload-jobvite_requisition_jobviteclosed" value="1" />
    <div class="card-header" id="jobvite_requisition_jobviteclosed-container">
        <h4 class="mb-0">
            <button class="btn btn-link btn-block text-left collapsed" type="button"
                data-toggle="collapse" data-target="#jobvite_requisition_jobviteclosed" aria-expanded="false"
                aria-controls="collapseOne">
                REQUISITIONS TO CLOSE <span id="jobvite_requisition_jobviteclosed-counter"></span> <i class="fa fa-chevron-right"></i>
            </button>
        </h4>
    </div>

    <div id="jobvite_requisition_jobviteclosed" class="collapse " aria-labelledby="dailyactivityone"  data-parent="#jobvite">
        <div class="card-body">
            @if(LD::checkFeatureFlag('recruiting-dashboard-update-descriptions'))
                <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                    <b>Description:</b> Jobvite requisitions that no longer require recruiting activity and should be
                    reviewed for closure based on current NTH needs.<br>
                    <b>Timeline:</b> Review daily and mark completed within 1 business day from the created date.
                    Any requisition to close that is not marked completed after 1 business day will be highlighted in red.</p>
            @endif
        <?php  echo H::getHelper("JobviteRequisition")->getDataTableHtml("jobvite_requisition_jobviteclosed")?>
        </div>
    </div>
</div>


<script>
        const requisitionData = ["jobvite_requisition_jobviteopen","jobvite_requisition_jobviteclosed"];
 
        $(document).on('submit', '#dashboardjobvite', function(e) {
            e.preventDefault();
            var sData = $("#dashboardjobvite").serializeArray();
            for(var i=0; i < requisitionData.length;i++) {
                var tempObject = requisitionData[i].replace(/ /g,"_");
               
                if($("#reload-"+tempObject).val()==1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');

                    $.ajax({
                        url: "{{route('jobvite.reloadrequisitiondata')}}?type="+requisitionData[i],
                        type: "POST",
                        data: {filters: sData,
                            _token: _token
                        },
                        success: function(response) {
                            var jsonObject = JSON.parse(response);
                            var type = jsonObject.type;
                            var idObject = type.replace(/ /g,"_");
                            $("#"+idObject+'-counter').html(jsonObject.counter);
                        },
                    });
                } else {
                   $("#dataTable-"+tempObject).DataTable().ajax.reload();
                }
            }

        });

</script>
