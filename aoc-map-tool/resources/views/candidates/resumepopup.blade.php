<?php 
use App\Helpers\H;

?>
<x-modal>
    <x-slot name="id">resumepopup-popup</x-slot>
    <x-slot name="size">w50</x-slot>
    <x-slot name="title">Resume | <?php echo $candidateName?></x-slot>
   
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 notes-form text-right">
                <?php
                    if($showDownload) { ?>
                        <a href="{{ route('pdfview',['download'=>'pdf','id'=>$id]) }}" onclick="downloadPDF()">Download Resume</a>
                    <?php } ?>
            </div>
            <div class="col-md-12 notes-form">
                <?php echo nl2br(ltrim($resume))?>
            </div>
        </div> 
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<style>
    p img {
        width:100%;
    }
    .w35 {
        width:35%;
    }
    .w25 {
        width:25%;
    }
    .notes.form-control {
        height:130px;
    }

    .w35 {
        width:35%;
    }
    .w25 {
        width:25%;
    }
    .notes.form-control {
        height:130px;
    }
    div.editable {
        width: 300px;
        height: 200px;
        border: 1px solid #ccc;
        padding: 5px;
    }

    strong {
    font-weight: bold;
    }

    #notes {
        background-clip: padding-box;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        color: #495057;
        display: block;
        font-size: .9rem;
        font-weight: 400;
        line-height: 1.6;
        padding: 0.375rem 0.75rem;
        transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        width: 100%;
        height: 150px;
    }
    #notes:focus {
        background-color: #fff;
        border-color: #a1cbef;
        box-shadow: 0 0 0 0.2rem rgb(52 144 220 / 25%);
        color: #495057;
        outline: 0;
    }
</style>

