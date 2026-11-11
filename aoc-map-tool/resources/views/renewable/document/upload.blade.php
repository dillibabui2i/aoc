<?php
use App\Helpers\H;
use App\Helpers\LD;

$coreSettingData = H::getCoreSettings();
?>

@extends('layouts.master')
@section('title', 'Renewable Document Upload')
@section('content')

<div class="login-cnt">
    <div id="login-box" class="left-side col-md-3">
        
        <div id="document-container-form" class="document-row">
            <form id="login-form" enctype="multipart/form-data" class="form" action="{{route('renewabledocument.save')}}" method="post">
                @csrf
                <!-- {{ csrf_field() }} -->
                <div class="logo">
                    <img src="{{ asset('images/logo.png') }}" alt="Renewable Doc" />
                </div>
                <div class="login-title">
                    <h1>Upload Renewable Document</h1>
                </div>

                @if ($errors->any())
                    <div class="col-sm-12">
                        <div class="alert  alert-warning alert-dismissible fade show" role="alert">
                            @foreach ($errors->all() as $error)
                            <span>
                                <p>{{ $error }}</p>
                            </span>
                            @endforeach
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                @endif

                @if (session('success'))
                <div class="col-sm-12">
                    <div class="alert  alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                @endif

                <?php if($data) { ?> 
                    <input type="hidden" name="renewable_id" id="renewable_id" value="<?php echo $data->id?>"/>
                    <p>&nbsp;</p>
                    <div class="form-group">
                        <label for="renewable_name" class="">Renewable: <?php  echo $data->name?></label><br />
                    </div>
                    <div class="form-group">
                        <label for="renewable_expiringon" class="">Expiring on:  <?php  echo H::getFormatedDate($data->expiration_date)?></label><br />
                    </div>
                    <p>&nbsp;</p>
                    <div class="form-group">
                        <!-- <label for="username" class="">Username</label><br /> -->
                        <input type="file" name="renewable_document[]" placeholder="Upload file" id="renewable_document" multiple class="form-control login">
                        <div class="invalid-feedback"><?php echo $errors->first('email'); ?></div>
                    </div>
                    
                    <div class="form-group">
                        <input type="submit" name="submit" class="btn btn-info login-btn btn-md" value="Upload">
                    </div>
                <?php } ?>

            </form>
        </div>



        <div class="login-sec-row">
            <div>
                <p>© Copyright <?php echo date('Y'); ?> Angels of Care. All rights reserved.</p>
                <p>
                    <a href="#">Privacy Policy</a>
                </p>
                <hr />
            </div>
        </div>
    </div>
    <div class="col-md-9 right-side">
        <div class="content-box">
            <div class="inner-content">
                <h2>{{ isset($coreSettingData['login_title'])? $coreSettingData['login_title'] : '' }}</h2>
                <div class="content-cnt">
                    <span>
                        {{isset($coreSettingData['login_content'])? $coreSettingData['login_content'] : ''}}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .left-side .document-row {
        flex: 1;
        display: flex;
        padding: 24px 16px;
        flex-flow: row;
        justify-content: center;
    }
</style>
@stop