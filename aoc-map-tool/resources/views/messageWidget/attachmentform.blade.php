<?php
    $messageTypes = config('constant.messageType');
?>
@extends('layouts.myaccount')
@if($mode == 'add')
    @section('title', 'Add New SMS Attachment')
@else
    @section('title', 'Edit SMS Attachment')
@endif
@section('content')
<!-- Begin Page Content -->

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            @if ($mode == 'add')
                <h1 class="h3 mb-2 text-gray-800">Add New SMS Attachment</h1>
            @else
                <h1 class="h3 mb-2 text-gray-800">Edit SMS Attachment</h1>
            @endif
            <a href="{{ route('managemessageattachment.list') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
        </div>
    </div>
    <!-- DataTales Example -->
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
    <div>
        <form method="POST" id="add-form-validate" enctype='multipart/form-data' action="{{ route('managemessageattachment.save') }}">
            <input type="hidden" name="id" id="id" value="{{($mode == 'edit') ? $data['id'] : ''}}">
            @csrf
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="message_types" class="text-left col-md-3">Message Type</label>
                    <select class="form-control col-md-6" id="message_types" name="message_types">
                        <option value=""> Please Select </option>
                        <?php foreach($messageTypes as $messageType) { ?>
                            <option value="<?php echo $messageType?>" {{($mode == 'edit' && $messageType == $data['message_types']) ? 'selected' : ''}}><?php echo $messageType?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="name" class="text-left col-md-3">Name</label>
                    <input type="text" class="form-control col-md-6" id="name" name="name" value="{{($mode == 'edit') ? $data['name'] : ''}}">
                </div>
                <div class="form-group col-md-12">
                    <label for="description" class="text-left col-md-3">Description</label>
                    <textarea class="form-control col-md-6" id="description" name="description">{{($mode == 'edit') ? $data['description'] : ''}}</textarea>
                </div>
                <div class="form-group col-md-12">
                    <label for="file_path" class="text-left col-md-3">File Upload</label>
                    <input type="file" class="form-control col-md-6" id="file_path" name="file_path" value="">
                    @if($mode == 'edit')
                        <br><span class="col-md-2" style="line-height: 40px;">{{$data['url']}}</span>
                    @endif
                </div>
                <div class="form-group col-md-12">
                    <label for="status" class="text-left col-md-3">Status</label>
                    <select class="form-control col-md-6" id="status" name="status">
                        <option value=""> Please Select </option>
                        <option value="1" {{($mode == 'edit' && 1 == $data['status']) ? 'selected' : ''}}>Enable</option>
                        <option value="0" {{($mode == 'edit' && 0 == $data['status']) ? 'selected' : ''}}>Disabled</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
        </form>
    </div>
</div>
<script type="text/javascript">
    $("#add-form-validate").validate({
        // Specify validation rules
        rules: {
            message_types: "required",
            name: "required",
            @if($mode == 'add')
            file_path: "required",
            @endif
            status: "required",
        },
        errorPlacement: function() {
            return true;
        },
    });
</script>
<!-- /.container-fluid -->
@endsection