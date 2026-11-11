@section('title', 'Manage Whiteboard')
@section('datatabletop')
<div class="form-group col-md-6 head-title">
    <h1 class="h3 mb-2 text-gray-800">Manage Whiteboard
        @canPermission('managewhiteboard.add')
            <a title="Add User" href="{{route('managewhiteboard.add')}}"><i class="fa fa-plus-circle"></i></a>
        @endcanPermission
    </h1>
</div>
@if ($errors->any())
<div class="alert alert-danger">
    <button type="button" class="close" data-dismiss="alert">×</button>
    {!! implode('', $errors->all('<div>:message</div>')) !!}
</div>
@endif
@if ($message = Session::get('error'))
<div class="alert alert-danger">
    <button type="button" class="close" data-dismiss="alert">×</button>
    {{ $message }}
</div>
@endif
@if ($message = Session::get('success'))
<div class="alert alert-success">
    <button type="button" class="close" data-dismiss="alert">×</button>
    {{ $message }}
</div>
@endif
@endsection
@extends('layouts.datatable')

@section('datatablebottom')
<script type="text/javascript">
    jQuery(document).ready(function() {
        table.on('draw', function() {
            jQuery('.disable-it').on('click', function(){
                if (confirm("Are you sure want to remove this record?")) {
                    window.location.href = $(this).attr('data-href');
                }
                return false;
            });
        });
    });
</script>
@endsection
