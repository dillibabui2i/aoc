@extends('layouts.myaccount')
@section('title', 'Create Permission ')
@section('content')
<!-- Begin Page Content -->


<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Create Permission </h1>
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
        <form method="POST" id="add-form-validate" action="{{ route('saveroutes') }}">
               @csrf
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="" class="text-left">Group Name</label>
                        <input type="text" class="form-control" id="group" name="group"  value="">
                    </div>

                    <div class="form-group col-md-12">
                        <label for="" class="text-left">Name</label>
                        <input type="text" class="form-control" id="name" name="name"  value="">
                    </div>

                    <div class="form-group col-md-12">
                        <label for="role" class="text-left">Routes </label>
                        <select name="route_name" id="route_name" class="form-control">
                            <?php foreach($routes as $route) { ?> 
                                <option value="<?php echo $route?>"><?php echo $route?></option>
                           <?php } ?>
                        </select>
                    </div>

                    <div class="form-group col-md-12">
                        <label for="" class="text-left">Related Routes</label>
                        <input type="text" class="form-control" id="related_routes" name="related_routes"  value="">
                        
                    </div>
                    <div  class="form-group col-md-12"><label>&nbsp;</label><span>NOTE: Add comma seperated permission name from the above dropdown</span></div>

                    <div class="button-container col-md-12" style="clear:both">
                        <div class=""><input type="submit" class="btn btn-info login-btn btn-md" id="save-btn" value="Save"></div>
                    </div>

                </div>

       </form>

    </div>
  
</div>

@endsection