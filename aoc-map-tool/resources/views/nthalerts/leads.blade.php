<?php
use App\Helpers\H;
use App\Helpers\LD;

$nthalertsFilter = [
    [
        'label'=>'Me Mode',
        'name'=>'nthlead_memode',
        'type'=>'checkbox',
        'value'=>false
    ],
    [
        'label'=>'⏱ Late',
        'name'=>'nthlead_late',
        'type'=>'checkbox',
        'value'=>false
    ],
    [
        'label'=>'State',
        'name'=>'nthlead_state',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Region',
        'name'=>'nthlead_region',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label'=>'Branch',
        'name'=>'nthlead_branch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'Recruiter',
        'name'=>'nthlead_recruiter',
        'type'=>'multiselect',
        'source'=>H::getHelper("ConstantHelper")->getRecruiters('id')
],
[
        'label'=>'Category',
        'name'=>'nthlead_category',
        'type'=>'multiselect',
        'source'=>[
            ["value"=>"lead","text"=>"Lead"],
            ["value"=>"inq","text"=>"Inquiry"]
        ]
],
[
        'label' => 'Status',
        'name' => 'nthlead_status',
        'type' => 'multiselect',
        'source' => [
            ["value"=>"New","text"=>"New"],
            ["value"=>"Pending","text"=>"Pending"],
            ["value"=>"Resolved","text"=>"Resolved"],
        ]
],

];
  
?>
        <div class="card">
            <div class="card-header" id="dailyactivityone">
                <h3 class="mb-0">
                    <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                        data-target="#nth_leads" aria-expanded="false" aria-controls="collapseOne">
                        NTH Leads and Inquiries <span id="nth_leads-counter"></span><i class="fa fa-chevron-right"></i>
                    </button>
                    @if(H::canPermissions(['is_recruiting_admin','is_recruiting_manager', 'view-nthleads-inquiries']))
                        <div class="col-md-2 text-right pull-left"><a href="{{route('nthalerts.list')}}" class="dashboard-showall" title="Show All NTH Leads">Show All NTH Leads</a></div>
                    @endif
                </h3>
            </div>
        
            <div id="nth_leads" class="collapse" aria-labelledby="dailyactivityone"  data-parent="#recruiting">
                <div class="filters-common">
                    @component('components.datatable-filters',
                    ['filters'=>$nthalertsFilter,'formname'=>'nth_leadsform'])
                    @endcomponent
                </div>
                <div class="card-body">
                    @if(LD::checkFeatureFlag('recruiting-dashboard-update-descriptions'))
                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                            <b>Description:</b> Leads and Inquiries that have been submitted by clinicians from the NTH
                            Alerts Landing Page. All records require recruiter review, follow-up, and documentation of the
                            next step.<br>
                            <b>Timeline:</b> Review and document next steps within 1 business hour from the created date
                            &amp; time (central time zone). Any lead or inquiry that is still in a New status after 1
                            business hour will be highlighted in red.</p>
                    @endif
                <?php echo H::getHelper("NTHAlerts")->getNTHAlertsDatatable('nth_leads')?>
                </div>
            </div>
        </div>

    <script>
       const nthLeads = ['nth_leads'];

    $(document).on('submit', '#nth_leadsform', function(e) {
        e.preventDefault();
        var serializeData = $("#nth_leadsform").serializeArray();
        //console.log(serializeData);
        for(var i=0; i < nthLeads.length;i++) {
            var tempObject = nthLeads[i].replace(/ /g,"_");
            if($("#reload-"+tempObject).val()==1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('nthalerts.reloadleadsandinquiries') }}?type="+nthLeads[i],
                    type: "POST",
                    data: {
                        filters: serializeData,
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
    $("#nth_leadsform").trigger("submit");


        $(document).on("change",'#nthlead_state',function(){
            var state = $(this).val().join(",");
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
               url: "{{ route('getstatebranch') }}",
               type: "GET",
               data: {
                   state: state,
                   _token: _token
               },
               success: function(response) {
                   $('#nthlead_branch').empty();
                   $.each(response, function( index, value ) {
                       $("#nthlead_branch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#nthlead_branch").multiselect("rebuild");
               },
           });
        });

        $(document).on("click",'#nthlead_memode',function() {
            $("#nthlead_recruiter").multiselect("clearSelection");
        });
        $(document).on("change",'#nthlead_recruiter',function() {
            $('#nthlead_memode').prop('checked', false);
        });
        $('#nthlead_state').change(function(){
            if ($(this).val().indexOf('TX') !== -1) {
                $('#nth_leadsform select[name="nthlead_region"]').multiselect('enable');
            } else {
                $('#nth_leadsform select[name="nthlead_region"]').multiselect('disable');
            }
        })
        $('#nthlead_state .clearall').click(function() {
            $('#nthlead_state select[name="nthlead_region"]').multiselect('disable');
        });
    </script>