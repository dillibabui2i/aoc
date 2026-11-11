
@section('title', 'Forget Password')



<!DOCTYPE html>
<html lang="en">
    <head>
		@include('layouts.header')

    </head>
    <body >

<div class="container">
    <div class="row justify-content-center align-items-center " style="height: 70vh;S">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading font-weight-bold">Forget Password </div>
                <hr>
                @if($errors->any())
                    <b style="color: red">{{$errors->first()}}</b>

                @endif

                <div class="panel-body">
                     <form id="login-form" class="form" action="{{route('sendmail')}}" method="post">
                        {{ csrf_field() }}

                        <div class="form-group">
                            <p> You will recieve reset password link in your email address if entered email address exists in our file.</p>


                             <div class="form-group col-md-6">
                                <label for="email" class="">Email Address</label><br>
                                <input type="text" name="email" id="email" class="form-control login">
                                <div class="invalid-feedback"><?php echo $errors->first('email'); ?></div>
                            </div>

                        </div>

                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <button type="submit" class="btn btn-primary">
                                    Submit
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


    </body>
    <script src="{{ mix('js/app.js') }}"></script>


</html>
