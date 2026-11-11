<?php
    use App\Helpers\H;
    use App\Helpers\LD;
?>
<div class="card-body">
    <div
        class="filters-common"
        data-bucket-filter="true"
        data-global-filter-fields='{"state":"intakestafftransfersstate","region":"intakestafftransfersregion","branch":"intakestafftransfersbranch"}'
    >
        @component(
            'components.datatable-filters',
            [
                'filters' => H::getHelper("IntakeCoordinatorStaffTransfers")->getFilters(),
                'formname' => 'intakestafftransfersform'
            ]
        )
        @endcomponent
    </div>
    <?php
        $IntakeStaffTransfers = H::getHelper("IntakeCoordinatorStaffTransfers")->getBuckets();
    ?>
    @foreach($IntakeStaffTransfers as $bucketKey => $bucket)
        <div class="card" data-follows-global="true">
            <div class="card-header" id="intake_coordinator_stafftransfers-{{ $bucketKey }}">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                        data-target="#{{ $bucketKey }}" aria-expanded="false" aria-controls="collapseOne">
                        {{ $bucket['heading'] }}
                        <span id="intake-{{ $bucketKey }}-counter"></span>
                        <i class="fa fa-chevron-right"></i>
                    </button>
                </h4>
            </div>
            <div id="{{ $bucketKey }}" class="collapse" aria-labelledby="stafftransfers"
                data-parent="#intake_coordinator_childs">
                {!! $bucket['description'] !!}
                @if(!empty($bucket['bucketFitler']))
                    <div class="filters-common">
                        @component(
                            'components.datatable-filters',
                            [
                                'filters' => H::getHelper("IntakeCoordinatorStaffTransfers")->{$bucket['bucketFitler']}(),
                                'formname' => 'intakestafftransfersform_' . $bucketKey
                            ]
                        )
                        @endcomponent
                    </div>
                @endif
                <div class="card-body">
                    {{ H::getHelper("IntakeCoordinatorStaffTransfers")->getDataTableHtml(
                        $bucketKey,
                        (!empty($bucket['bucketFitler'])) ?
                        ['intakestafftransfersform', 'intakestafftransfersform_' . $bucketKey] :
                        'intakestafftransfersform',
                        (isset($bucket['defaultSortColumnNumber']) ? $bucket['defaultSortColumnNumber'] : [2, "desc"])
                    ) }}
                </div>
            </div>
        </div>
    @endforeach
</div>

<script>
    $('#intakestafftransfersstate').change(function(){
		if ($(this).val().indexOf('TX') !== -1) {
			$('#intakestafftransfersform select[name="intakestafftransfersregion"]').multiselect('enable');
		} else {
			$('#intakestafftransfersform select[name="intakestafftransfersregion"]').multiselect('disable');
		}
	});
	$('#intakestafftransfersform .clearall').on('click', function() {
		$('#intakestafftransfersform select[name="intakestafftransfersregion"]').multiselect('disable');
	});
    @if(LD::checkFeatureFlag('add-soc-clinician-filter-into-intake-scheduled-bucket'))
        $('#intakestafftransfersform #intakestafftransfersClinician').multiselect({includeSelectAllOption: true,enableCaseInsensitiveFiltering: true});
    @endif
</script>
