@extends('layouts.myaccount')
@section('title', 'Recruiter Scorecard')
@section('content')
<!-- Begin Page Content -->
<?php
    use App\Helpers\H;
    $filters = [
        [
            'label' => 'Date',
            'name' => 'daterange',
            'type' => 'daterange',
            'fromdate' => H::getFormatedDate($startDate),
            'todate' => H::getFormatedDate($endDate),
        ]
    ];
?>
<div class="container-fluid skillsreport-main">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Recruiter Scorecard</h1>
        </div>
    </div>

    <!-- Table -->
    <div>
        <div class="card-body">
            @component('components.datatable-filters',['filters'=>$filters])
            @endcomponent
			<div class="table-responsive">
				<table class="table table-bordered" width="100%" cellspacing="0">
					<thead>
						<tr>
							<th>Recruiter</th>
                            @foreach(array_reverse($months) as $month)
                                <th>{{date("M - Y", strtotime($month."-01"));}}</th>
                            @endforeach
                            <th>Average</th>
						</tr>
					</thead>
					<tbody>
						@foreach($result as $recruiter => $data)
                            <tr>
                                @php list($id, $recruiter_id, $name) = explode("#", $recruiter) @endphp
                                <td>{{$name}}</td>
                                @php $totalScore = 0; $totalScoreCount = 0;@endphp
                                @foreach(array_reverse($months) as $month)
                                    @php
                                        if (array_key_exists($month, $data)) {
                                            $totalScore += $data[$month];
                                            $totalScoreCount++;
                                            echo "<td><span ondblclick=\"recruiterScorecard('view', '".$id."')\">".$data[$month]."%</span></td>";
                                        } else {
                                            echo "<td>0%</td>";
                                        }
                                    @endphp
                                @endforeach
                                <td>{{round($totalScore / $totalScoreCount)}}%</td>
                            </tr>
                        @endforeach
					</tbody>
				</table>
			</div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function setReportFilterRequest() {
        let _token = $('meta[name="csrf-token"]').attr('content');
		$.post( "{{route('recruiterScorecardReport')}}", $('#datatableFilterSearchForm').serialize() + '&_token=' + _token, function( data ) {
			$('.table-responsive').html($(data).find('.table-responsive').html());
			//eval($(data).find('#nrrreportsjs').html());
		});
	}
	$(document).ready(function() {
		$('#datatableFilterSearch').click(function() {
			setReportFilterRequest();
		});

        $('#datatableFilterSearchForm .clearall').click(function() {
            var frmObject = $("#"+$(this).attr("data-formname"));
			var filters =  jQuery.parseJSON($(this).attr("data-filter"));

			$.each(filters, function(i, item) {
				var elem = frmObject.find("#"+item.name);
				if(item.type=='checkbox') {
					if(item.value==true){
						elem.prop('checked', true);
					} else {
						elem.prop('checked', false);
					}
				} else if(item.type=='select') {
					elem.val(elem.prop("selectedIndex", 0).val())
				} else if(item.type=='multiselect') {
					elem.multiselect("clearSelection");
				} else if(item.type == 'daterange') {
					$('#from_date').val(item.fromdate);
					$('#to_date').val(item.todate);
				} else {
					elem.val((elem.data('defaultval') ? elem.data('defaultval') : ''));
				}
			});
			setReportFilterRequest();
		});
    });
</script>
@endsection
