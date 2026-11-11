<div class="card">
    <div class="card-header" id="school_setup_manager">
        <h2 class="mb-0">
            <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed"
            type="button" data-toggle="collapse"
                data-target="#school_setup_childs" aria-expanded="false" aria-controls="collapseThree">
                School Set Ups<i class="fa fa-chevron-right"></i>
            </button>
            <div class="col-md-2 text-right pull-left">
                <a
                    href="{{route('schoolsetups.showall')}}"
                    class="dashboard-showall"
                    title="Show All School Set Ups">Show All School Set Ups</a>
            </div>
        </h2>

    </div>
    <div id="school_setup_childs" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
        <div class="card-body">
            <div class="card">
                <!---Bucket goes here--->
                @include('schoolSetUp.openSchoolSetUpRequests')
            </div>
        </div>
    </div>
</div>
