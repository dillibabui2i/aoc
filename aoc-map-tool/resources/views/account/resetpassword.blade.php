@extends('layouts.master')

@section('title', 'Reset Password')


@section('content')
    <div class="login-section">
        <div class="container">
            <div class="row justify-content-center align-items-center">
                <div class="col-md-5 login-column">
					 @if ($errors->any())
					<div class="col-sm-12">
						<div class="alert  alert-warning alert-dismissible fade show" role="alert">
							@foreach ($errors->all() as $error)
								<span><p>{{ $error }}</p></span>
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
				
                    <div id="login-box" class="col-md-12">
                        <form id="login-form" class="form" action="{{route('reset.password.post')}}" method="post">
                        	@csrf <!-- {{ csrf_field() }} -->
							<input type="hidden" name="token" value="<?php echo $token?>">
                            <h3 class="text-center">Reset Password</h3>
                            <div class="invalid-feedback"><?php echo $errors->first('auth'); ?></div>
                           
							 <div class="form-group">
                                <label for="email" class="">Your Email</label><br>
                                <input type="text" name="email" id="email" class="form-control login">
                            </div>
						   
                            <div class="form-group">
                                <label for="password" class="">New Password</label><br>
                                <input type="password" name="password" id="password" class="form-control login">
                            </div>
							
							<div class="form-group">
                                <label for="password_confirmation" class="">Confirm New Password</label><br>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control login">
                            </div>
                           
                            <div class="form-group">
                               
                                <input type="submit" name="submit" class="btn btn-info login-btn btn-md" value="Submit">
                                
                            </div>

                            
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop