<?php
    use App\Helpers\H;
?>
<x-modal>
    <x-slot name="id">journalNoteDetails</x-slot>
    <x-slot name="size">w30</x-slot>
    <x-slot name="title">After Hours Journal Notes</x-slot>
    <x-slot name="extraHeader">
    </x-slot>
    <x-slot name="body">
        <div class="row border-bottom p-2">
            <label for="client" class="col-sm-3 col-form-label">Client</label>
            <div class="col-sm-9 d-inline-block pt-2 pb-2">{{$details->patient_name}}</div>
        </div>
        <div class="row border-bottom p-2">
            <label for="datetime" class="col-sm-3 col-form-label">Date Time</label>
            <div class="col-sm-9 d-inline-block pt-2 pb-2">{{H::getFormatedDateTime($details->added_date)}}</div>
        </div>
        <div class="row p-2">
            <label for="note" class="col-sm-3 col-form-label">Note</label>
            <div
                class="col-sm-9 d-inline-block overflow-auto pt-2 pb-2"
                style="max-height:150px;"
            >{!! $details->notes !!}</div>
        </div>
    </x-slot>
    <x-slot name="footer">
        <div class='w-100 text-center'>
            <h3>
                <b>
                    Do you confirm that you have completed any actions needed for this note?
                </b>
            </h3>
            <div class="buttons">
                <button
                    class="btn btn-primary"
                    onclick="clearNotes('{{$details->id}}'); $('#journalNoteDetails').modal('hide');">Yes</button>
                <button class="btn btn-primary" onclick="$('#journalNoteDetails').modal('hide');">No</button>
            </div>
        </div>
    </x-slot>
</x-modal>
