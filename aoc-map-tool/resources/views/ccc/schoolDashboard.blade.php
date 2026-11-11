<div class="card-body">
    <div class="card">
        <div class="card-header" id="dailyactivityone">
            <h4 class="mb-0">
                <button class="btn btn-link btn-block text-left collapsed col-md-10 pull-left" type="button"
                data-toggle="collapse" data-target="#ccc_school_setup_childs" aria-expanded="false" aria-controls="collapseOne">
                    School Set Up<span id="ccc_school_setup_childs1-counter"></span><i class="fa fa-chevron-right"></i>
                </button>
            </h4>
        </div>
        <div id="ccc_school_setup_childs" class="collapse" aria-labelledby="headingThree" data-parent="#client_care_childs">
            <div class="card-body">
                <!---Bucket goes here--->
                @include('ccc.schoolSetup')
                @include('ccc.pendingSchoolSetupRequest')
            </div>
        </div>
    </div>
</div>
