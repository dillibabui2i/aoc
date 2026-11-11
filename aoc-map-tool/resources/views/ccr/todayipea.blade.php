<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    if (LD::checkFeatureFlag('ccm-to-day-ipea-bucket-filters')) {
        $todayipeafilter = [
            [
                'label'=>'Me Mode',
                'name'=>'ipeamemode',
                'type'=>'checkbox',
                'value'=>true
            ],

            [
                'label'=>'State',
                'name'=>'ipeastate',
                'type'=>'multiselect',
                'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
            ],
            [
                'label'=>'Region',
                'name'=>'ipearegion',
                'type'=>'multiselect',
                'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
                'disabled' => true
            ],[
                'label'=>'Branch',
                'name'=>'ipeabranch',
                'type'=>'multiselect',
                'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
            ],[
                'label'=>'Clinical Care Manager',
                'name'=>'ipeaclinicalcaremanager',
                'type'=>'multiselect',
                'source'=>H::getHelper('ConstantHelper')->getClinicalCareManager()
            ],
        ];
    }
?>
<div class="card-header">
    <h4 class="mb-0">
        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
            data-target="#ccr_today_ipea" aria-expanded="false" aria-controls="collapseOne">
            TODAY's IPEAS <span id="ccr_today_ipea-counter"></span><i class="fa fa-chevron-right"></i>
        </button>
    </h4>
</div>
<div id="ccr_today_ipea" class="collapse" data-parent="#clinical_case_childs">
    <div class="col-md-12 text-left bucket-subheading">Today's IPEAs displays  all IPEAs scheduled for today based on current selected filters.</div>
    <div class="filters-common">
      @if(LD::checkFeatureFlag('ccm-to-day-ipea-bucket-filters'))
        @component('components.datatable-filters',['filters'=>$todayipeafilter,'formname'=>'todayipeaform'])
        @endcomponent
      @endif
    </div>
    <div class="card-body">
        <?php echo H::getHelper("ClinicalCaseManager")->getDataTableHtml(
                    'ccr_today_ipea',
                    '#todayipeaform',
                    [
                        'state' => 'ipeastate',
                        'branch' => 'ipeabranch',
                        'region' => 'ipearegion',
                    ],
        ) ?>
    </div>
</div>
<?php if(LD::checkFeatureFlag('ccm-to-day-ipea-bucket-filters')){ ?>
<script type="text/javascript">
$(document).ready(function(){
    $(document).on('submit', '#todayipeaform', function(e) {
        e.preventDefault();
        var serializeData = $("#todayipeaform").serializeArray();
        let _token = $('meta[name="csrf-token"]').attr('content');
        var tempObject = 'ccr_today_ipea';
            if($("#reload-"+tempObject).val()==1) {
                $.ajax({
                    url: "{{ route('dashboard.reloadcountclinicalcase') }}?type=ccr_today_ipea",
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
                $("#dataTable-clinical-case-manager-"+tempObject).DataTable().ajax.reload();
            }
    });
    $("#todayipeaform").trigger("submit");


});
</script>
<?php }else {?>
<script type="text/javascript">
    $(document).ready(function(){
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{ route('dashboard.reloadcountclinicalcase') }}?type=ccr_today_ipea",
        type: "POST",
        data: {
            _token: _token
        },
        success: function(response) {
            var jsonObject = JSON.parse(response);
            var type = jsonObject.type;
            var idObject = type.replace(/ /g,"_");
            $("#"+idObject+'-counter').html(jsonObject.counter);
        },
    });
});
</script>
<?php }?>
</script>
