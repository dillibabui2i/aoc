<?php
use App\Helpers\H;
?>
@section('title', 'Manage Map Ignore')
@section('datatabletop')
<div class="form-group col-md-6 head-title">
    <h1 class="h3 mb-2 text-gray-800">Manage Map Ignore</h1> 
    <a title="Manage Map Ignore" href="{{ route('map.ignoreform') }}"><i class="fa fa-plus-circle"></i></a>
</div>
@endsection

@extends('layouts.datatable')
@section('datatablebottom')
<script>
    /* Formatting function for row details - modify as you need */
function format(d) {
    var jsonObject = jQuery.parseJSON(d.value);
    var text = '<table class="mapignore" cellpadding="5" width="100%" cellspacing="0" border="0" style="padding-left:50px;">';
    $.each(jsonObject, function(i, item) {
        text+='<tr>';
        $.each(item, function(ind, v) {
            text+='<td>'+ind+':</td>' +
            '<td>' +v+
            '</td>';
        });
        text+='</tr>';
    });
    text+='</table>';
    return (text);
}
$(document).ready(function(){
    $('#dataTable').on('init.dt', function(e, settings, json) {
        $(".sorting_1").addClass("dt-control");
    });
   
    
// Add event listener for opening and closing details
$(document).on('click', 'td.dt-control', function () {
        var tr = $(this).closest('tr');
        
        var row = table.row(tr);
        
        if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
        } else {
            // Open this row
            row.child(format(row.data())).show();
            tr.addClass('shown');
        }
    });
});

</script>

@endsection