<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
$allBuckets =H::getHelper("Triage")->getBuckets();


$followupType = '1D';
if($type=='triage_24h_follow_up') {
	$followupType = '1D';
} else if($type=='triage_48h_follow_up') {
	$followupType = '2D';
} else if($type=='triage_7d_follow_up') {
	$followupType = '7D';
} else if($type=='triage_30d_follow_up') {
	$followupType = '30D';
}

?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-triage-<?php echo $type?>" width="100%" cellspacing="0">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {?>
					<td><?php echo $data['label']?></td>
				<?php }  ?>
				<td>Action</td>
			</tr>
		</thead>
	</table>
</div>

<script>
	function <?php  echo $type?>(){
		var fields_<?php echo $type?> = '<?php echo json_encode($dataTable)?>';
		fields_<?php echo $type?> = JSON.parse(fields_<?php echo $type?>);
		var columns_<?php echo $type?> = [];
		$.each(fields_<?php echo $type?>, function(key, val) {
            columns_<?php echo $type?>.push({
                data: val.data,
                name: val.name,
                visible: typeof val.visible !== 'undefined' ? val.visible : true,
                width: typeof val.width !== 'undefined' ? val.width : ''
            });
        });
		columns_<?php echo $type?>.push({
			data: null,
			width: '5%',
			orderable: false,
			render: function(data, type, row) {
				var btn =  '<a href="javascript:void(0)" title="Document Outcome" onclick=\'getOutcomeDetails("' + row[
                'id'] + '","<?php echo $followupType?>")\' ><img src="{{ asset("images/phone-icon.png") }}" width="15px"></a>&nbsp;';
                <?php if(LD::checkFeatureFlag('triage-call-details')) {?>
                    btn += '<a href="javascript:void(0)" title="Call Details" onclick=\'getTriageCallDetails("' + row[
                'id'] + '")\'><i class="fa fa-eye" aria-hidden="true"></i></a>';
                <?php }?>
                return btn;

			}
		});
		var <?php echo $type?> = $("#dataTable-triage-<?php echo $type?>").DataTable({
			"order": [
				[2, "desc"]
			],
			"columnDefs": [

				{ className: "", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },

			],
			"ajax": {
				"url": "{{route('triage.getTriageData')}}?type=<?php echo $type?>",
				data: function(d) {
					d.filters =  $("#triageform").serializeArray();
                    if (window.GlobalLocationFilter) {
                        window.GlobalLocationFilter.applyToParams(d, "#dataTable-triage-@php echo $type @endphp", {
                            state: "triagestate",
                            branch: "triagebranch"
                        });
                    }
				}
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": columns_<?php echo $type?>,
			"drawCallback": function (oSettings, json) {
				@if (LD::checkFeatureFlag('dashboard-past-due-count'))
				var api = this.api();
            	var json = api.ajax.json();
					$("#<?php echo $type?>-counter").html("<span>("+<?php echo $type?>.page.info().recordsTotal+" | "+json.pastduecount+" Past Due)</span>");
				@else
					$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");
				@endif
			},
			createdRow: function( row, data, dataIndex ) {
				if(data.contactwithcaller=='0') {
					$(row).addClass("dashboard-older-record");
				}

    		},
			"initComplete": function(settings, json) {

				jQuery("#dataTable-triage-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-triage-<?php echo $type?>_filter input").on("keyup", function(e) {

					var search = jQuery("#dataTable-triage-<?php echo $type?>_filter input").val();

					clearTimeout(searchDelay);
					searchDelay = setTimeout(function() {
						if (search != null && search.length >=3)
						<?php echo $type?>.search(search).draw();
						if (search.length==0 && e.keyCode==8)
						<?php echo $type?>.search(search).draw();
					}, 1000);
				});
			},

		});
	}


</script>
