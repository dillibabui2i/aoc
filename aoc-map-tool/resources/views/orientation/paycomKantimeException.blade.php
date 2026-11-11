<?php
use App\Helpers\H;
use App\Helpers\LD;


$paycomKantimeException = [

        [
            'label'=>'Discrepancy Type',
            'name'=>'discrepancy_type',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getPaycomKantimeDiscrepancyType()
        ]


    ];
?>


<div class="card-body">
    <div class="card">
        <div class="card-header" id="dailyactivityone">
            <h4 class="mb-0">
                <button class="btn btn-link btn-block text-left collapsed col-md-9 pull-left" type="button" data-toggle="collapse"
                    data-target="#paycom_kantime_exception" aria-expanded="false" aria-controls="collapseOne">
                    Exception <span id="paycom_kantime_exception-counter"></span><i class="fa fa-chevron-right"></i>
                </button>
               
            </h4>
        </div>

        <div id="paycom_kantime_exception" class="collapse" aria-labelledby="dailyactivityone" data-parent="#orientation">
            
            <div class="filters-common">
                @component('components.datatable-filters', ['filters' => $paycomKantimeException, 'formname' => 'paycomkantimeexceptionform'])
                @endcomponent
            </div>
            <div class="card-body">
                <?php  echo H::getHelper("PaycomKantimeException")->getDataTableHtml("paycom_kantime_exception")?>
            </div>
        </div>
    </div>
</div>


<script>
        const paycomkantimeexception_bucket = ["paycom_kantime_exception"];
 
        $(document).on('submit', '#orientationForm,#paycomkantimeexceptionform', function(e) {
            e.preventDefault();
            var sData = $("#orientationForm,#paycomkantimeexceptionform").serializeArray();
            console.log(paycomkantimeexception_bucket.length);
            for(var i=0; i < paycomkantimeexception_bucket.length;i++) {
                var tempObject = paycomkantimeexception_bucket[i].replace(/ /g,"_");
                console.log($("#reload-"+tempObject).val());
                if($("#reload-"+tempObject).val()==1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');

                    $.ajax({
                        url: "{{route('paycomkantime.reloadexception')}}?type="+paycomkantimeexception_bucket[i],
                        type: "POST",
                        data: {filters: sData,
                            _token: _token
                        },
                        success: function(response) {
                            var jsonObject = JSON.parse(response);
                            var type = jsonObject.type;
                            var idObject = type.replace(/ /g,"_");
                            $("#"+idObject+'-counter').html(jsonObject.counter);
                        },
                    });
                } else {
                   $("#dataTable-paycomkantime-"+tempObject).DataTable().ajax.reload();
                }
            }

        });

</script>
