<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center" id="hospitalizations-heading">
        <h3 class="mb-0">
            <button
                class="btn btn-link btn-block text-left collapsed"
                type="button"
                data-toggle="collapse"
                data-target="#hospitalizations-collapse"
                aria-expanded="false"
                aria-controls="hospitalizations-collapse"
            >
                Hospitalizations <i class="fa fa-chevron-right"></i>
            </button>
        </h3>
        <a href="{{ route('hospitalization.index') }}" title="Show All Hospitalizations" class="dashboard-showall">Show All Hospitalizations</a>
    </div>
    <div id="hospitalizations-collapse" class="collapse" aria-labelledby="hospitalizations-heading" data-parent="#intensive-case-management-2">
        <div class="card-body">
            <div class="v-app">
                <div id="vbc-bucket-hospitalization"></div>
            </div>
        </div>
    </div>
</div>
