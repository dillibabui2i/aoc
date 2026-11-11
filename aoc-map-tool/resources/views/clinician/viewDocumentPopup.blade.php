<?php
    use App\Helpers\H;
    use App\Helpers\LD;
?>

<x-modal>
    <x-slot name="id">ViewClinicianDocumentModal</x-slot>
    <x-slot name="size">w80</x-slot>
    <x-slot name="title">View Document</x-slot>
   
    <x-slot name="body">
        <div class="row d-flex">
            <?php if(strtolower($type)=='pdf') { ?>
                <iframe  src = "ViewerJS/#../<?php echo $path?>" title="Document Popup" width='100%' height='500' allowfullscreen webkitallowfullscreen></iframe>
            <?php } else { ?>
                <img src="<?php echo $path?>" width="100%" alt="Document" />
            <?php } ?>
            
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

