<?php
use App\Helpers\H;
use App\Helpers\LD;

$skillsets = H::getHelper("ConstantHelper")->getSkillSets();
$branches = H::getHelper("ConstantHelper")->getBranches();
//var_dump($data);
?>
@extends('layouts.myaccount')
@section('title', 'Manage IPEA Attachment')
@section('content')
<!-- Begin Page Content -->


<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage IPEA Attachment</h1>
            <a href="{{ route('manageipeaattachment.grid') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
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
      
        <form method="POST" id="add-form-validate" enctype='multipart/form-data' action="{{ route('manageipeaattachment.save') }}">
            <input type="hidden" name="id" id="id" value="{{ isset($data)?$data->id:''}}">
                @csrf
                <div class="form-row">
                    
                    <div class="form-group col-md-12">
                        <label for="file_path" class="text-left col-md-3">File Upload</label>
                        <input type="file" class="form-control col-md-6" id="file_path" name="file_path" value=""> <br>
                        <span class="col-md-3" ><?php echo isset($data['file_path'])?$data['file_path']:''?></span>
                    </div>

                    
                    
                    <div class="form-group col-md-12">
                        <label for="skills-dropdwon" class="text-left col-md-3">Skills</label>
                        <select class="selectpicker" id="skills-dropdwon" name="skills[]" multiple data-live-search="true">
                        <?php foreach($skillsets as $skill) { ?>
                                 <option value="<?php echo $skill?>" <?php if(isset($data['skills']) && in_array($skill,explode(",",$data["skills"]))) { ?> selected="selected" <?php } ?>><?php echo $skill?></option> 
                            <?php } ?>
                        </select>
                    </div>

                   
                    <div class="form-group col-md-12">
                        <label for="facilities-dropdown" class="text-left col-md-3">Facilities</label>
                        <select class="selectpicker" id="facilities-dropdwon" name="facilities[]" multiple data-live-search="true">
                        <?php foreach($branches as $branch) { ?>
                            <option value="<?php echo $branch->id?>"  <?php if(isset($data['facilities']) && in_array($branch->id,explode(",",$data["facilities"]))) { ?> selected="selected" <?php } ?>><?php echo $branch->branch?></option>
                        <?php } ?>
                        </select>
                    </div>
                </div>
    </div>
    
    <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>

    </form>
</div>
<style>
span.multiselect-native-select {
    width: 50%;
}
span.multiselect-native-select .btn-group {
    width: 100%;
}
.multiselect-container.dropdown-menu.show {
    width:100%;
    height: 400px;
    overflow-y: auto;
}
</style>
<script>
    $(document).ready(function() {
        $('#facilities-dropdwon').multiselect({
            includeSelectAllOption: true
        });
        $('#skills-dropdwon').multiselect({
            includeSelectAllOption: true
        });
    });
</script>
<!-- /.container-fluid -->
@endsection