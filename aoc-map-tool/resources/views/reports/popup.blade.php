<?php 
use App\Helpers\H;
?>

<x-modal>
    <x-slot name="id">nrr-popup</x-slot>
    <x-slot name="size">w100</x-slot>
    <x-slot name="title">Report Details</x-slot>
   
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 notes-form">
                <?php if(isset($results) && is_array($results) && !empty($results)) {?>
                <table id="popup-datatable" class="table table-striped" width="100%">
                    <thead> 
                        <tr>
                            <?php foreach($results[0] as $key=> $res) { ?> 
                            <th class="tbl-heading dt-nowrap"><?php echo $key?></th>
                            <?php  } ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $cntr=0;
                        foreach($results as $r) { 

                            $class = ($cntr%2)?"even":"odd";
                            $cntr++;
                        ?>
                    
                        <tr>
                            <?php 
                            foreach($r as $key=> $res) { 
                                    echo '<td class="tbl-body dt-nowrap">';
                                    if (strtolower($key) == 'percentage') {
                                        echo $res . '%';
                                    } elseif(strtolower($key) == 'date') {
                                        echo H::getFormatedDate($res);
                                    } elseif(strtolower($key) == 'pay rate') {
                                        $tmp = (is_numeric($res)) ? number_format($res, 2, '.', ',') : $res;
                                        echo ($tmp != '')?'$' .$tmp:'';
                                    } elseif(strtolower($key) == 'client') {
                                        $clientDetail = explode("|", $res);
                                        if (!empty($clientDetail[1])) {
                                            $link = '<a href="javascript:void(0)"';
                                            if (!empty($clientDetail[2]))
                                                $link .= 'onclick="getClientDetails(&quot;'
                                                    . $clientDetail[1]
                                                    . '&quot;,&quot;'
                                                    . $clientDetail[2]
                                                    . '&quot;,&quot;'
                                                    . $clientDetail[3]
                                                    . '&quot;)">';
                                            else
                                                $link .= 'onclick="getClientDetails(&quot;'.$clientDetail[1].'&quot;)">';
                                            $link .= $clientDetail[0] . '</a>';
                                            echo $link;
                                        } else {
                                            echo $clientDetail[0];
                                        }
                                    } elseif(strtolower($key) == 'action') {
                                            $link = '';
											if($res != strip_tags($res)) {
												$link = $res;
											} else {
												$actions = explode(",", $res);
												foreach($actions as $action) {
													if (!empty($action)) {
														list($id, $act) = explode("#", $action);
														if ($act == 'tracker' && !empty($id)) {
															$link .= '<a href="javascript:void(0)" title = "Tracker Detail"';
															$link .= 'onclick="openRecruiterDetails(&quot;'.$id.'&quot;,&quot;&quot;,&quot;true&quot;)"><i class="fa fa-eye" aria-hidden="true"></i>';
															$link .= '</a>';
														}
														if ($act == 'candidate' && !empty($id)) {
															$link .= '<a href="javascript:void(0)" title = "Candidate Detail"';
															$link .= 'onclick="openCandidateDetails(&quot;'.$id.'&quot;,&quot;true&quot;)"><i class="fa fa-eye" aria-hidden="true"></i>';
															$link .= '</a>';
														}
														if ($act == 'ipea' && !empty($id)) {
															$link .= '<a href="javascript:void(0)" title = "IPEA Detail"';
															$link .= 'onclick="updateNurseIpeaInfo(&quot;'.$id.'&quot;)"><i class="fa fa-vcard" aria-hidden="true"></i>';
															$link .= '</a>';
														}
													}
												}
											}
                                            echo $link;
                                    } else {
                                        echo $res;
                                    }
                                    echo "</td>";
                              } ?>
                        </tr>
                    
                    <?php } ?>
                    </tbody>
                </table>
                <?php } ?>
             
            </div>
        </div> 
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script>
@if(in_array(Route::currentRouteName(),['getcandidateconversionreportpopup']))

function initPopupDataTable() {

  if ($.fn.DataTable.isDataTable('#popup-datatable')) {
    $('#popup-datatable').DataTable().clear().destroy(true);
  }

  $('.popup-toolbar').remove();

  window.popupTable = $('#popup-datatable').DataTable({
    ordering: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100, 250],
    columnDefs: [{ orderable: false, targets: -1 }],
    dom:
      '<"popup-toolbar d-flex justify-content-between align-items-center"lf>' +
      'rt' +
      '<"bottom"ip>'
  });

  injectPopupExportButton();
}

function injectPopupExportButton() {
  if (!$('.dataTables_filter #downloadCandidateConversionPopup').length) {
    $('.dataTables_filter').prepend(`
      <a href="javascript:void(0)"
         id="downloadCandidateConversionPopup"
         class="mr-2"
         title="Export">
        <i class="fa fa-download"></i>
      </a>
    `);
  }
}

function collectPopupTableData() {
  const table = window.popupTable;

  // determine the index of the Action column
  let actionColIndex = null;
  const headers = [];

  table.columns(':visible').every(function (index) {
    const text = $(this.header()).text().trim();
    if (text === 'Action') {
      actionColIndex = index;
    } else {
      headers.push(text);
    }
  });

  const rows = [];

  table
    .rows({ search: 'applied', order: 'applied' })
    .every(function () {
      const data = this.data();
      const row = [];

      data.forEach((cell, i) => {
        if (i === actionColIndex) return;

        row.push(
          $('<div>').html(cell).text().trim()
        );
      });

      rows.push(row);
    });

  return { headers, rows };
}

function exportPopupTableToCsv() {
  const data = collectPopupTableData();

  if (!data.rows.length) {
    alert('No data to export');
    return;
  }

  let csv = '';
  csv += data.headers.join(',') + '\n';

  data.rows.forEach(row => {
    csv += row
      .map(cell => `"${cell.replace(/"/g, '""')}"`)
      .join(',') + '\n';
  });

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);

  const a = document.createElement('a');
  a.href = url;
  a.download = 'candidate_conversion_popup.csv';
  a.click();

  URL.revokeObjectURL(url);
}


$(document)
  .off('shown.bs.modal', '#nrr-popup')
  .on('shown.bs.modal', '#nrr-popup', function () {
    initPopupDataTable();
  });

if (typeof exportInProgress === 'undefined') {
  var exportInProgress = false;
}

$(document).on('hide.bs.modal', '#nrr-popup', function () {
  if (document.activeElement) {
    document.activeElement.blur();
  }
});

$(document)
  .off('click', '#downloadCandidateConversionPopup')
  .on('click', '#downloadCandidateConversionPopup', function () {
    if (exportInProgress) return;

    exportInProgress = true;
    exportPopupTableToCsv();

    setTimeout(() => {
      exportInProgress = false;
    }, 500);
  });
@else
jQuery.extend(jQuery.fn.dataTable.ext.type.order, {
  "duration-pre": function (data) {
    const match = data.match(/(\d+)H\s*(\d+)M/);
    if (!match) return 0;
    const hours = parseInt(match[1], 10);
    const minutes = parseInt(match[2], 10);
    return hours * 60 + minutes; // convert to total minutes
  }
});
$(document).ready(function(){
    $('#popup-datatable').dataTable({
    "ordering": {{ (isset($sortColumn) && $sortColumn == true) ? 'true' : 'false' }},
	@if((isset($sortColumn) && $sortColumn == true))
	columnDefs: [
        { type: 'duration', targets: 4 }
    ]
	@endif
});
});
@endif
</script>
