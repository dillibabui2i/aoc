<?php
    use Illuminate\Support\Facades\Storage;
    use App\Helpers\H;
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
<style>
    .feature-photos .card{
        min-width: auto;
    }
</style>
<section class="feature-photos">
    <div class="row">
        @foreach($feature_photos as $fphoto)
            <div class="col-md-3 mb-2">
                <a href="{{$fphoto->document_filename}}" data-fancybox="image-gallery">
                    <img alt="{{$fphoto->document_filename}}" class="w-75" src="{{$fphoto->document_filename}}" />
                </a>
            </div>
        @endforeach
    </div>
</section>
<script>
    $(document).ready(function() {
        $("[data-fancybox]").fancybox({
            
        });
    });
</script>

