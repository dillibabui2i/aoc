<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
$allBuckets =H::getHelper("RecruitingException")->getBuckets();
$recruiters = H::getHelper("ConstantHelper")->getRecruitersList(true);
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-recruiting-exception-<?php echo $type?>" width="100%" cellspacing="0">
		<thead>
			<tr>
				@if($typeFunction == 'Nthactivetrackerdisabledrecruiter')
					<td><input type="checkbox" name="massUpdateRecruiterSelectAll" id="massUpdateRecruiterSelectAll"/></td>
				@endif
				<?php foreach($dataTable as $key => $data) {
						if($type=='mismatched_first_shift_confirmed' && $key=='Candidate_Start_Date') {
							$fields[] = ["data"=>['_'=>'Candidate_Start_Date.display','sort'=>'Candidate_Start_Date.timestamp'],"name"=>'Candidate_Start_Date.timestamp',"searchable"=>false];
						} else {
							$fields[] = ["data"=>$key,"name"=>$key];
						}
					?>
				<td><?php echo $data?></td>
				<?php }  ?>
				@if($typeFunction != 'Nthactivetrackerdisabledrecruiter')
				<td>Action</td>
				@endif
			</tr>
		</thead>
	</table>
</div>

<script>
	@if (LD::checkFeatureFlag('disabled-recruiter-exceptions'))
	function assignRecruiter(button) {
		button.disabled = true;
		let _token = $('meta[name="csrf-token"]').attr('content');
		var selectedIds = [];
		$('input[name="massUpdateRecruiter[]"]:checked').each(function() {
			selectedIds.push(this.value);
		});
		$.ajax({
            url: "{{route('massUpdateRecruiter')}}",
            type: "POST",
            data: {
				_token: _token,
				ids: selectedIds.join(","),
				recruiter_id: $('#massUpdateRecruiterId').val(),
			},
            success: function(response) {
                button.disabled = false;
				if (response.success == 1) {
					$("#dataTable-recruiting-exception-nth_active_tracker_disabled_recruiter").DataTable().ajax.reload();
				}
				$('input[name="massUpdateRecruiter[]"]').prop('checked', false);
				$('.dataTables_length').hide();
            },
        });
	}

	function showHideMassUpdateRecruiter(isChecked) {
		$('#dataTable-recruiting-exception-nth_active_tracker_disabled_recruiter_wrapper div.updateall').remove();
		if (isChecked) {
			var recruiterList = {!!json_encode($recruiters)!!};
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
						html += '<select class="form-control" name="massUpdateRecruiterId" id="massUpdateRecruiterId" style="display: inline-block; width: 170px;">';
						$.each(recruiterList, function(i, item) {
							html += '<option value="' + item.value + '">' + item.text + '</option>';
						});
						html += '</select>';
						html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="assignRecruiter(this)" class="btn btn-primary" style="display: inline-block;">Save</button>';
						html += '</div>';
			$('#dataTable-recruiting-exception-nth_active_tracker_disabled_recruiter_wrapper').prepend(html);
		}
	}
	$('#massUpdateRecruiterSelectAll').click(function(){
		$('input[name="massUpdateRecruiter[]"]').prop('checked', $(this).prop("checked"));
		showHideMassUpdateRecruiter($(this).is(':checked'));
	});
	@endif
	function <?php  echo $type?>(){
		var fields_<?php echo $type?> = '<?php echo json_encode($fields)?>';
		fields_<?php echo $type?> = JSON.parse(fields_<?php echo $type?>);
		var columns_<?php echo $type?> = [];
		@if($typeFunction == 'Nthactivetrackerdisabledrecruiter')
		columns_<?php echo $type?>.push({
			data: null,
			width: '5%',
			orderable: false,
			render: function(data, type, row) {
				return '<input type="checkbox" name="massUpdateRecruiter[]" value="'+data.id+'"/>';
			}
		});
		@endif
		$.each(fields_<?php echo $type?>, function(key, val) {
            columns_<?php echo $type?>.push({
                data: val.data,
                name: val.name,
                visible: typeof val.visible !== 'undefined' ? val.visible : true,
                width: typeof val.width !== 'undefined' ? val.width : ''
            });
        });
		@if($typeFunction != 'Nthactivetrackerdisabledrecruiter')
		columns_<?php echo $type?>.push({
			data: null,
			width: '5%',
			orderable: false,
			render: function(data, type, row) {
				<?php if($type=='mismatched_first_shift_confirmed') { ?>
					return '<a href="javascript:void(0)" onclick="showScheduleForm(\''+data.id+'\', \'CANDIDATE STARTS\');"><i class="fa fa-calendar" aria-hidden="true"></i></a> | <a href="javascript:void(0)" onclick="openRecruiterDetails(\''+data.id+'\');"><i class="fa fa-eye" aria-hidden="true"></i></a>';

				<?php } else { ?>
				return '<a href="javascript:void(0)" onclick="openNthChecklistDetails(\''+data.id+'\', true)"><i class="fa fa-eye" aria-hidden="true"></i></a>';
				<?php } ?>
			}
		});
		@endif
		var <?php echo $type?> = $("#dataTable-recruiting-exception-<?php echo $type?>").DataTable({
			"order": [
				[2, "desc"]
			],
			"columnDefs": [

				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },

			],
			"ajax": {
				"url": "{{route('dashboard.getrecrutingexception')}}?type=<?php echo $type?>",
				data: function(d) {
					d.filters =  $("#recruitingexception").serializeArray();
                    if (window.GlobalLocationFilter) {
                        window.GlobalLocationFilter.applyToParams(d, "#dataTable-recruiting-exception-<?php echo $type ?>",  {
                            state: "recruiter-state",
                            region: "recruitingregion",
                            branch: "recruiter-aoc_facility_id"
                        });
                    }
				},
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": columns_<?php echo $type?>,
			"drawCallback": function (oSettings, json) {
				$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");

				$('.select-all-{{$type}}.td').off().bind('click', function() {
					showHideUdateButtonFor{{$typeFunction}}($('.select-all-{{$type}}.td:checked').length ? true : false);
				});

				@if (LD::checkFeatureFlag('disabled-recruiter-exceptions'))
				$('input[name="massUpdateRecruiter[]"]').off().bind('click', function() {
					showHideMassUpdateRecruiter($('input[name="massUpdateRecruiter[]"]:checked').length ? true : false);
				});
				@endif
			},
			"initComplete": function(settings, json) {

				jQuery("#dataTable-recruiting-exception-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-recruiting-exception-<?php echo $type?>_filter input").on("keyup", function(e) {

					var search = jQuery("#dataTable-recruiting-exception-<?php echo $type?>_filter input").val();

					clearTimeout(searchDelay);
					searchDelay = setTimeout(function() {
						if (search != null && search.length >=3)
						<?php echo $type?>.search(search).draw();
						if (search.length==0 && e.keyCode==8)
						<?php echo $type?>.search(search).draw();
					}, dataTableDelay);
				});
			},

		});
	}


	if($("#reload-<?php echo $type?>").val()==1) {
		let _token = $('meta[name="csrf-token"]').attr('content');
		$.ajax({
			url: "{{ route('recruitingexceptioncount') }}?type=<?php echo $type?>",
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
	} else {
		$("#dataTable-<?php echo $type?>").DataTable().ajax.reload();
	}

</script>
