<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
$recruiters = H::getHelper("ConstantHelper")->getRecruiters('id');
$recruiterDropdown = '<select class="form-control" name="assign_recruiter" id="assign_recruiter">';
    $recruiterDropdown.='<option value="">Select Recruiter</option>';
    foreach($recruiters as $recruiter) {
        $recruiterDropdown.='<option value="'.$recruiter['value'].'">'.$recruiter['text'].'</option>';
    }
$recruiterDropdown.='</select>';
$loggedIn = H::getLoggedInUser();
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
    <table class="table table-bordered" id="dataTable-<?php echo $type?>"
        width="100%" aria-describedby="">
        <thead>
            <tr>
                <?php foreach($dataTable as $key => $data) {
                    $fields[] = ["data"=>$key,"name"=>$key]; 
                    
                    if($key=='select') {
                        $checkbox = '<td>';
                        $checkbox .= '<input class="form-input" id="select-all-checkbox-'.$type.'" type="checkbox" name="select-'.$type.'" value="0" />';
                        $checkbox .= '</td>';
                        echo $checkbox;
                    } else {
                    ?>
					<td><?php echo $data?></td>
				<?php } } ?>
                <th>Action</th>
            </tr>
        </thead>
    </table>
</div>
<?php
	$fields[] = ["data"=>"Action"];
?>
<script>

        window.nthNewestOnlyEnabled = {{LD::checkFeatureFlag('nth-leads-latest-lead-per-clinician') ? 'true' : 'false' }};

        $(document).ready(function(){
			$('#select-all-checkbox-nth_leads').click(function() {
				$('.select-all-nth_leads.td').prop('checked', $(this).prop("checked"));
				showHideUdateButtonForNTHLeads($(this).is(':checked'));
			});
		});

	function showHideUdateButtonForNTHLeads(isChecked) {
		$('#dataTable-nth_leads_wrapper div.updateall').remove();
		if (isChecked) {
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';

            @canPermission(['selfassignment-nthleads-inquiries'])
			html += '<div class="col-auto pull-left"><button type="button" onclick="assignRecruitertoSelf()" class="btn btn-primary">Assign to Self</button></div>';
            @endcanPermission

            @canPermission(['re-assignment-nthleads-inquiries'])
            html+='<div class="col-auto pull-left"><?php echo addslashes($recruiterDropdown)?></div>';
            html += '<div class="col-auto pull-left"><button type="button" onclick="reAssignRecruiter()" class="btn btn-primary">Reassign</button></div>';
			@endcanPermission
            html += '</div>';
			$('#dataTable-nth_leads_wrapper').prepend(html);
		}
	}
    var existingRecruiters = [];
    var targetRecruiter = '<?php echo $loggedIn['id']?>';
    var ids = [];
    var totalSelected = 0;

    function getPublicText(selectedRecruiter=''){

        existingRecruiters = [];
        ids = [];
        totalSelected = 0;

        $('.select-all-nth_leads.td:checked').each(function() {
            if($(this).attr("data-recruiter").trim()!=='') {
               existingRecruiters.push($(this).attr("data-recruiter"));
            } else {
                existingRecruiters.push("Unassigned");
            }
            ids.push($(this).val());
            totalSelected++;
        });

        var counts = {};
        $.each(existingRecruiters, function(_, value) {
            counts[value] = (counts[value] || 0) + 1;
        });

        var formattedString = Object.entries(counts)
        .map(([key, value]) => `[${value}] (${key})`)
        .join(", ");


        if(selectedRecruiter.trim()!=='') {
            var text = "You are about to reassign "+totalSelected+" tasks from "+formattedString+" to "+selectedRecruiter+" Are you sure you want to proceed? ";
        } else {
            var text = "You are about to assign "+totalSelected+" tasks to yourself. "+formattedString+" Are you sure you want to proceed?";
        }
        return text;

    }

    function assignRecruitertoSelf(){

        targetRecruiter = "<?php echo $loggedIn['id']?>";

        var text = getPublicText();

        $("#nthconfirmationbody").html(text);
        $("#nthconfirmation").modal("show");
    }

    function reAssignRecruiter(){


        targetRecruiter = $("#assign_recruiter").val(); // Get selected value
        var selectedText = $("#assign_recruiter option:selected").text(); // Get selected text

        var text = getPublicText(selectedText);

        $("#nthconfirmationbody").html(text);
        $("#nthconfirmation").modal("show");
    }

    function submitRequest(){
        let _token = $('meta[name="csrf-token"]').attr('content');
        if(targetRecruiter!=='') {

            $.ajax({
                url: "{{route('nthalerts.recruiterassignment')}}",
                type: 'post',
                data: {
                    _token: _token,
                    ids: ids,
                    targetRecruiter: targetRecruiter,
                },
                success: function(response) {
                    if(response.success ==1 ) {
						$('#dataTable-nth_leads').DataTable().ajax.reload();
                        $("#nthconfirmation").modal("hide");
                    }

                }
            });

        }
    }


        function {{ $type }}() {

        var fields_{{$type}} = @json($fields);
        var columns_{{$type}} = [];
        $.each(fields_{{$type}}, function(key, val) {

           if (window.nthNewestOnlyEnabled && val.data === 'clinician_name') {
             columns_{{$type}}.push({
               data: 'clinician_name',
               name: 'clinician_name',
               render: function (data, type, row, meta) {
                 const isSearching = meta.settings.oPreviousSearch?.sSearch?.length > 0;

                 if (isSearching) {
                   return data;
                 }

                 const newTotal = parseInt(row.tooltip_new_count || 0);
                 const pendingTotal = parseInt(row.tooltip_pending_count || 0);

                 const total = newTotal + pendingTotal;

                 if (total <= 1) {
                   return data;
                 }

                 let parts = [];
                 if (newTotal > 0) parts.push(`${newTotal} New`);
                 if (pendingTotal > 0) parts.push(`${pendingTotal} Pending`);

                 const tooltipText = `${total} open (${parts.join(', ')})`;

                 return `
                      <span
                            data-toggle="tooltip"
                            data-placement="top"
                            title="${tooltipText}"
                            style="cursor: help; border-bottom: 1px solid #333a86;"
                        >
                            ${data}
                        </span>
                    `;
               }
             });
             return;
           }

          if (val.data === 'ticket_id') {
            columns_{{$type}}.push({
              data: 'ticket_id',
              name: 'ticket_id',
              render: function (data, type, row) {

                if (!row.superseded_id) {
                  return data;
                }

                return `
                        ${data}
                          <a href="javascript:void(0);"
                             onclick="getNthLeadsInquiriesDetail('${row.superseded_id}')"
                             title="Open newest record">
                              <span class="badge badge-primary ml-1">
                                   Superseded by LEAD-${row.superseded_id}
                              </span>
                          </a>
                       `;
              }
            });
            return;
          }

            columns_{{$type}}.push({
                data: val.data,
                name: val.name,
                visible: typeof val.visible !== 'undefined' ? val.visible : true,
                width: typeof val.width !== 'undefined' ? val.width : ''
            });
        });

        var {{$type}} = $("#dataTable-{{$type}}").DataTable({
           
                "order": [
                    [2, "desc"]
                ],
                "columnDefs": [
                    @canPermission(['selfassignment-nthleads-inquiries','re-assignment-nthleads-inquiries'])
                    {
                        orderable: false,
                        className: 'select-checkbox',
                        targets:   0
                    },
                    @endcanPermission
                    { className: "dt-nowrap", "targets": [1] },
                    { className: "dt-nowrap", "targets": [-1] },

                ],
                "ajax": {
                    "url": "{{route('nthalerts.getleadsandinquiries')}}?type={{$type}}",
                    data: function(d) {
                        d.filters = $("#nth_leadsform").serializeArray();
                    },
                },
            "deferRender": true,
            "processing": true,
            "serverSide": true,
            "columns": columns_{{$type}},
            'createdRow': function (row, data) {
              if (parseInt(data.is_late) === 1) {
                $(row).addClass('dashboard-older-record');
              }
            },
            "drawCallback": function (settings) {

              const json = settings.json;
              if (json && json.customCounter) {
                $("#{{$type}}-counter").html(json.customCounter);
              } else {
                $("#{{$type}}-counter")
                  .html("(" + this.api().page.info().recordsTotal + ")");
              }

                @canPermission(['selfassignment-nthleads-inquiries','re-assignment-nthleads-inquiries'])
                $('.select-all-{{$type}}.td').off().bind('click', function() {
					showHideUdateButtonForNTHLeads($('.select-all-{{$type}}.td:checked').length ? true : false);
				});
                @endcanPermission
            },
            "initComplete": function(settings, json) {

                jQuery("#dataTable-{{$type}}_filter input").off("keyup.DT input.DT");
                var searchDelay = null;
                jQuery("#dataTable-{{$type}}_filter input").on("keyup", function(e) {

                    var search = jQuery("#dataTable-{{$type}}_filter input").val();

                    clearTimeout(searchDelay);
                    searchDelay = setTimeout(function() {
                        if (search != null && search.length >=3)
                        {{$type}}.search(search).draw();
                        if (search.length==0 && e.keyCode==8)
                        {{$type}}.search(search).draw();
                    }, dataTableDelay);
                });
            },

        });
    }
</script>

<div class="modal fade" id="nthconfirmation" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Confirmation</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body" id="nthconfirmationbody">

        </div>
        <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="submitRequest()">Submit</button>
            <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
        </div>
        </div>
    </div>
</div>
