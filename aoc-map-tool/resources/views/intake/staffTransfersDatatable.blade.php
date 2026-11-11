<?php
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
?>
<input type="hidden" name="reload-{{ $type }}" id="reload-{{ $type }}" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-staffTransfers-{{ $type }}" width="100%" cellspacing="0">
		<thead>
			<tr>
				<?php foreach ($dataTable as $key => $data) { ?>
					<td><?php echo $data['label']; unset($dataTable[$key]['label']); ?></td>
				<?php }  ?>
			</tr>
		</thead>
	</table>
</div>

<script>
	function <?php  echo $type?>() {
		var fields_{{ $type }} = '<?php echo json_encode($dataTable)?>';
		fields_{{ $type }} = JSON.parse(fields_{{ $type }});
		var columns_{{ $type }} = [];
		$.each(fields_{{ $type }}, function(key, val) {
			columns_{{ $type }}.push(val);
		});
		var {{ $type }} = $("#dataTable-staffTransfers-{{ $type }}").DataTable({
			"order": [
				[{{$defaultSortColumnNumber[0]}}, "{{$defaultSortColumnNumber[1]}}"]
			],
			"columnDefs": [
				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1], "orderable": false },
			],
			"ajax": {
				"url": "{{route('intake.getintakestafftransfers')}}?type={{ $type }}",
				data: function (d) {
					d.filters = $('{{
						is_array($filterFormName) ?
						implode(",", array_map(function($x){ return '#'.$x ; }, $filterFormName)) :
						'#'.$filterFormName
					}}').serializeArray();

                    if (window.GlobalLocationFilter) {
                        window.GlobalLocationFilter.applyToParams(d, "#dataTable-staffTransfers-<?php echo $type?>");
                    }
				}
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": columns_{{ $type }},
			"drawCallback": function (oSettings, json) {
				$("#intake-{{ $type }}-counter " ).html("("+{{ $type }}.page.info().recordsTotal+")");
			},
			"initComplete": function (settings, json) {
				jQuery("#dataTable-staffTransfers-{{ $type }}_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-staffTransfers-{{ $type }}_filter input").on("keyup ", function(e) {
					var search = jQuery("#dataTable-staffTransfers-{{ $type }}_filter input").val();
					clearTimeout(searchDelay);
					searchDelay = setTimeout(function () {
						if (search != null && search.length >= 3)
							{{ $type }}.search(search).draw();
						if (search.length == 0 && e.keyCode == 8)
							{{ $type }}.search(search).draw();
					}, dataTableDelay);
				});
			},
		});
	}

	@if (!empty($filterFormName))
		$(document).on('submit', '{{
						is_array($filterFormName) ?
						implode(",", array_map(function($x){ return '#'.$x ; }, $filterFormName)) :
						'#'.$filterFormName
					}}', function(e) {
			e.preventDefault();
			var serializeData = $("{{
						is_array($filterFormName) ?
						implode(",", array_map(function($x){ return '#'.$x ; }, $filterFormName)) :
						'#'.$filterFormName
					}}").serializeArray();
			var tempObject = "{{ $type }}".replace(/ /g,"_");
			if($("#reload-"+tempObject).val()==1) {
				let _token = $('meta[name="csrf-token"]').attr('content');
				$.ajax({
					url: "{{route('intake.reloadcountintakestafftransfers')}}?type={{ $type }}",
					type: "POST",
					data: {
						filters: serializeData,
						_token: _token
					},
					success: function(response) {
						var jsonObject = JSON.parse(response);
						var type = jsonObject.type;
						var idObject = type.replace(/ /g,"_");
						$("#intake-"+idObject+'-counter').html(jsonObject.counter);
					},
				});
			} else {
				$("#dataTable-staffTransfers-"+tempObject).DataTable().ajax.reload();
			}
		});
		$("{{
			is_array($filterFormName) ?
			implode(",", array_map(function($x){ return '#'.$x ; }, $filterFormName)) :
			'#'.$filterFormName
		}}").trigger("submit");
	@endif
</script>
