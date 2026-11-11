<style>
    .ui-widget.ui-widget-content {
        z-index: 10000;
    }
</style>
<?php

?>
<div class="row pt-5">
    <div class="col-md-6">
        <label for="OfferRejectedReason">Offer Rejected Reason</label>
        <select name="OfferRejectedReason" id="OfferRejectedReason" class="form-control">
            <option value="">-- Select One --</option>
            @foreach(config('constant')['OfferRejectedReason'] as $k => $v)
                <option value="{{$v}}">{{$v}}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="row pt-5 pay-section hide">
    <div class="col-md-6">
        <label for="client">Client</label>
        <input type="input" class="form-control" id="Client" name="Client" value="" />
    </div>
    <div class="col-md-6">
        <label for="Payer">Payer</label>
        <input type="input" class="form-control" id="Payer"
            name="Payer">
    </div>
    <div class="col-md-6">
        <label for="Pay_Rate">Pay Rate</label>
        <input type="input" class="form-control" id="Pay_Rate"
            name="Pay_Rate">
    </div>
    <div class="col-md-6">
        <label for="GP">GP</label>
        <input type="input" class="form-control" id="GP"
            name="GP">
    </div>
</div>
<script>
    validation = {
        onfocusout:false,
        rules: {
            "Client": {
                required: true
            },
            "Payer": {
                required: true
            },
            "Pay_Rate": {
                required: true
            },
            "GP": {
                required: true
            },
        },
        messages: {
            Client: "Client is require",
            Payer: "Payer is require",
            Pay_Rate: "Pay_Rate is require",
            GP: "GP is require",
        },
        errorPlacement: function(error, element) {}
    };
    $(function(){
        $("#Client").autocomplete({
            source: "{{ route('getScheduleClient') }}",
            minLength: 2,
            select: function(event, ui) {
                $("#Payer").val(ui.item.payer);
            }
        });
        $(document).on('change','#OfferRejectedReason',function(e){
            if($(this).val() == 'Pay'){
                $('.pay-section').removeClass('hide');
            }else{
                $('.pay-section').addClass('hide');
            }
        });
    });
</script>
