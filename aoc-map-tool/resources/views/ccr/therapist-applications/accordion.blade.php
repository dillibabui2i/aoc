@php
    use App\Helpers\H;
    use App\Helpers\LD;

    /**
    * @see resources/views/ccr/ccmdashboard.blade.php
    */
@endphp

<div class="card-header" id="dailyactivityone">
    <h4 class="mb-0">
        <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button"
                data-toggle="collapse"
                data-target="#therapist-applications" aria-expanded="false" aria-controls="collapseOne">
            Therapist Applications <span id="therapist-applications-counter"></span><i
                class="fa fa-chevron-right"></i>
        </button>

        <div class="col-md-2 text-right pull-left p-1">
            <a href="{{route('therapist.get-applications', ['type' => 'therapy'])}}" class="dashboard-showall" title="Show All Candidates">Show All Candidates</a>
        </div>
    </h4>
</div>
<div id="therapist-applications" class="collapse" aria-labelledby="dailyactivityone"
     data-parent="#clinical_case_childs">
    <div class="col-md-12 text-left bucket-subheading"></div>
    <div class="filters-common" data-bucket-filter="true"
         data-global-filter-fields='{"state":"jobvitestate","region":"region","branch":"branch","stateValueMap":{!! json_encode(config('constant.dashboard_state')) !!}}'>
        @component('components.datatable-filters',['filters'=> $filters,'formname'=>'dashboardjobvitetherapy-ccm'])@endcomponent
    </div>

    <div class="card-body">
        <!-- Scheduled Clinical Interview -->
        <div class="card" data-follows-global="true">
            <input type="hidden" name="reload-ccm_scheduled_clinical_interview_therapy" id="reload-ccm_scheduled_clinical_interview_therapy" value="1" />
            <div class="card-header" id="jobvite-ccm_scheduled_clinical_interview_therapy">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                            data-toggle="collapse" data-target="#ccm_scheduled_clinical_interview_therapy"
                            aria-expanded="false" aria-controls="ccm_scheduled_clinical_interview_therapy">
                        Scheduled Clinical Interview <span id="ccm_scheduled_clinical_interview_therapy-counter"></span> <i class="fa fa-chevron-right"></i>
                    </button>
                </h4>
            </div>

            <div id="ccm_scheduled_clinical_interview_therapy" class="collapse" aria-labelledby="jobvite-ccm_scheduled_clinical_interview_therapy"
                 data-parent="#therapist-applications">
                <p style="text-align: left;">
                    <u><b>Responsible Party:</b> Therapy Clinical Manager</u><br>
                    <b>Description:</b> Candidates scheduled for the clinical interview, ready for the clinical interview with the Therapy Clinical Manager.<br>
                    <b>Timeline:</b> 1 business day after the scheduled date of the interview.
                </p>
                <div class="card-body">
                    <?php echo H::getHelper("Jobvite")->getDataTableHtml("scheduled_clinical_interview_therapy", 'ccm_', '#dashboardjobvitetherapy-ccm')?>
                </div>
            </div>
        </div>

        <!-- Offer Extended -->
        <div class="card" data-follows-global="true">
            <input type="hidden" name="reload-ccm_offer_extended_therapy" id="reload-ccm_offer_extended_therapy" value="1" />
            <div class="card-header" id="jobvite-ccm_offer_extended_therapy">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                            data-toggle="collapse" data-target="#ccm_offer_extended_therapy"
                            aria-expanded="false" aria-controls="ccm_offer_extended_therapy">
                        Offer Extended <span id="ccm_offer_extended_therapy-counter"></span> <i class="fa fa-chevron-right"></i>
                    </button>
                </h4>
            </div>

            <div id="ccm_offer_extended_therapy" class="collapse" aria-labelledby="jobvite-ccm_offer_extended_therapy"
                 data-parent="#therapist-applications">
                <p style="text-align: left;">
                    <u><b>Responsible Party:</b> Recruiter</u><br>
                    <b>Description:</b> All candidates who passed the interview and received a job offer letter. At this point, the recruiter can record a follow-up (if the candidate has not yet returned the signed job offer letter), or set the final status of the application.<br>
                    <b>Timeline:</b> 1 business day after the job letter be sent or the last follow-up date.
                </p>
                <div class="card-body">
                    <?php echo H::getHelper("Jobvite")->getDataTableHtml("offer_extended_therapy", 'ccm_', '#dashboardjobvitetherapy-ccm')?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        const ccmTherapyBuckets = ['ccm_scheduled_clinical_interview_therapy', 'ccm_offer_extended_therapy'];

        $(document).on('submit', '#dashboardjobvitetherapy-ccm', function (e) {
            e.preventDefault();
            var serializeData = $("#dashboardjobvitetherapy-ccm").serializeArray();
            for (var i = 0; i < ccmTherapyBuckets.length; i++) {
                var tempObject = ccmTherapyBuckets[i].replace(/ /g, "_");
                if ($.fn.DataTable.isDataTable('#dataTable-jobvite-' + tempObject)) {
                    $('#dataTable-jobvite-' + tempObject).DataTable().ajax.reload();
                }
            }
        });

        $("#dashboardjobvitetherapy-ccm").trigger("submit");
    });
</script>
