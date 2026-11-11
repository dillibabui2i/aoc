<?php
use App\Helpers\H;
use App\Helpers\LD;
?>
            <form id="login-form" enctype="multipart/form-data" class="form"
            action="{{route('azureservice.uploadfile')}}" method="post">
                    <p>&nbsp;</p>
                    @csrf
                    <!-- {{ csrf_field() }} -->
                    <div class="form-group">
                        <input type="file" name="document" placeholder="Upload file"
                         id="renewable_document"  class="form-control login">
                    </div>
                    
                    <div class="form-group">
                        <input type="submit" name="submit" class="btn btn-info login-btn btn-md" value="Upload">
                    </div>
            </form>
