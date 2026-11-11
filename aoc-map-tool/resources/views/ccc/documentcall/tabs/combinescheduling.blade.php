<?php
    
    $options = [
        '',
        'Yes',
        'No'
    ];
    
?>
<section class="tab-content-preferences">
    <div class="main-content">
        <div class="tab-content-area m-2">
            <div class="form-row mt-2 mb-2">
                <div class="form-group col-md-12 m-0 p-0 mr-5">
                        @include('ccc.documentcall.tabs.scheduling')
                </div>
                <div class="form-group col-md-12 m-0 p-0 mr-5">
                    @include('ccc.documentcall.tabs.monthlyscheduling')
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    $(document).on("change","#monthlyscheduling_upcomingvacation", function() {
        if($(this).val() == 'Yes') {
            $("#monthlyscheduling_nurseattendvacation_container").removeClass('invisible');
        } else {
            $("#monthlyscheduling_nurseattendvacation_container").addClass('invisible');
        }
    });

    $(document).ready(function(){
        $("#coverageshift_container").hide();
    });

</script>
