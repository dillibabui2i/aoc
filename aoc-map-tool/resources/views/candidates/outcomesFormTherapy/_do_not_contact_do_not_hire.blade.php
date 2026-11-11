@php
use App\Helpers\LD;

@endphp
<style>
    textarea.error{
        border: 1px dashed red;
        color: red;
        font-weight: 300;
    }
</style>
<div class="row pt-5">
    <div class="col-md-6 pb-3">
        <label for="Donothire">Reason</label>
        <select class="form-control" id="Donothire" name="Donothire">
            <option value="">--Select One---</option>
            @foreach(config('constant')['Donothire'] as $reason)
                <option value="{{$reason}}">{{$reason}}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label for="note">Detailed Reason</label>
        <textarea class="form-control" id="note" name="note"></textarea>
    </div>
</div>
<script>
    validation = {
        onfocusout:false,
        rules: {
            "Donothire": {
                required:true
            },
            "note": {
                required: function(element) {
                    return !($("#note").val() !== '');
                }
            }
        },
        messages: {
            Donothire: "Please fill the reason",
        },
        errorPlacement: function(error, element) {}
    };
</script>
