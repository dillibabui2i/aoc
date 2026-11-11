<?php
    use App\Helpers\H;
    use App\Helpers\LD;

    $orderToArray = [];
    foreach($followupData as $followup) {
        if(!empty($followup->orderDetails)) {
            $orders = json_decode($followup->orderDetails,true);
            foreach($orders as $order) {
                $orderToArray[] = $order;
            }
        }
    }
    $triageCallOutcome = config('constant')['triageCallOutcome'];
    if(LD::checkFeatureFlag('add-outcome-triage-followup-care-coordination')){
        array_push($triageCallOutcome,'Care Coordination Only; No Further Follow Ups');
    }
?>
<link href="{{ mix('css/jquery.datetimepicker.min.css') }}" rel="stylesheet">
<script src="{{ mix('js/jquery.datetimepicker.full.min.js') }}"></script>
<x-modal>
    <x-slot name="id">triageOutcomeDetailsModal</x-slot>
    <x-slot name="size">w80</x-slot>
    <x-slot name="title">Triage Calls /
        {{(isset($patientsDetails->Patient_FirstName) ? $patientsDetails->Patient_FirstName : '').' '.(isset($patientsDetails->Patient_LastName) ? $patientsDetails->Patient_LastName : '')}}
    </x-slot>
    <x-slot name="class">triageOutcomeDetailsModal</x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="tracker-details col-md-9">
                <div class="col-md-12 card-cnt p-0 d-flex">
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Client</h5>
                            <div class="detail-section">
                                <div class="row d-flex">
                                    <div class="col-md-6">
                                        <h5>
                                            <?php $name='';
                                                    if(isset($patientsDetails->Patient_FirstName)) {
                                                        $name.=$patientsDetails->Patient_FirstName;
                                                    }
                                                    if(isset($patientsDetails->Patient_MiddleName)) {
                                                        $name.=" ".$patientsDetails->Patient_MiddleName;
                                                    }
                                                    if(isset($patientsDetails->Patient_LastName)) {
                                                        $name.=" ".$patientsDetails->Patient_LastName;
                                                    }
                                                    echo trim($name);
                                                    ?>
                                        </h5>
                                        <p class="card-text">
                                            {{isset($patientsDetails->PatientAccount) ? $patientsDetails->PatientAccount : ""}}</p>
                                        <p class="card-text">
                                            {{isset($patientsDetails->Patient_City) ? $patientsDetails->Patient_City : ""}} {{isset($patientsDetails->Patient_State) ? $patientsDetails->Patient_State : ""}}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="card-text">
                                            {{isset($patientsDetails->Patient_DOB) ? "DOB: ".H::getFormatedDate($patientsDetails->Patient_DOB) : ""}}
                                        </p>
                                        <p class="card-text">
                                            <?php
                                                if(isset($patientsDetails->Patient_DOB)){
                                                    $diff = date_diff(date_create($patientsDetails->Patient_DOB), date_create(date("Y-m-d")));
                                                    echo "Age: ".$diff->format('%y');
                                                }
                                            ?>
                                        </p>
                                        <p class="card-text">
                                            {{isset($patientsDetails->CF_Weight) && $patientsDetails->CF_Weight != '' ? "Weight: ".$patientsDetails->CF_Weight : ""}}
                                        </p>
                                    </div>
                                </div>

                                <p class="card-text mt-3">
                                    {{isset($patientsDetails->Patient_Phone) ? $patientsDetails->Patient_Phone : ""}}
                                </p>
                                <p class="card-text">
                                    <span id="client-card-mobile">
                                        {{isset($patientsDetails->Patient_MobilPhone) && $patientsDetails->Patient_MobilPhone != '' ? $patientsDetails->Patient_MobilPhone.' (Mobile)' : ""}}
                                    </span>
                                </p>
                                <p class="card-text mt-2">
                                    {{isset($patientsDetails->Patient_Address1) ? $patientsDetails->Patient_Address1 : ""}},{{isset($patientsDetails->Patient_Address2) ? $patientsDetails->Patient_Address2 : ""}}
                                </p>
                                <p class="card-text">
                                    {{isset($patientsDetails->Patient_City) ? $patientsDetails->Patient_City : ""}}
                                    {{isset($patientsDetails->Patient_State) ? $patientsDetails->Patient_State : ""}}
                                    {{isset($patientsDetails->Patient_Zip) ? $patientsDetails->Patient_Zip : ""}}
                                </p>
                                <p class="card-text mt-4">
                                    {{isset($patientsDetails->Payer) ? $patientsDetails->Payer : ""}}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Original Call Details</h5>
                            <div class="detail-section">
                                <div class="row d-flex">
                                    <div class="col-md-6">
                                        <p class="card-text">
                                            {{H::getFormatedDate($triage->callDate)}}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="card-text">
                                            {{H::getFormatedTime($triage->callStartTime)}}
                                        </p>
                                    </div>
                                </div>
                                <div class="row d-flex mt-2">
                                    <div class="col-md-6">
                                        <p class="card-text">
                                            {{$triage->callerFirstName.' '.$triage->callerLastName}}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="card-text">
                                            {{$triage->clientRelationship}}
                                        </p>
                                    </div>
                                </div>
                                <p class="card-text mt-3">
                                    {{$triage->reason}}
                                </p>
                                <p class="card-text">
                                    {{$triage->description}}
                                </p>
                                <p class="card-text mt-3">
                                    {{$triage->bodySystem}}
                                </p>
                                <p class="card-text">
                                    {{$triage->careCoordination}}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 mt-3">
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label  class="w-100" for="triageCallOutcome">Outcome</label>
                            <select data='{{$followType}}' class="form-control" name="triageCallOutcome" id="triageCallOutcome">
                                <option data='{{$followType}}' value="">--- Select One ---</option>
                                @if(LD::checkFeatureFlag('triage-attempt-count-changes'))
                                    @php
                                        $arr = ['1D'=>'attempts_24','2D'=>'attempts_48','7D'=>'attempts_7day','30D'=>'attempts_30day'];
                                        $currentCount = $triage->{$arr[$followType]};
                                    @endphp

                                    @foreach($triageCallOutcome as $v)
                                        @if(strpos($v,'Attempt') !== false)
                                            @php
                                                $exx = explode(' ',$v);
                                            @endphp
                                            @if(($currentCount + 1) == $exx[1])
                                                <option value="{{$v}}">{{$v}}</option>
                                            @endif
                                        @else
                                            <option {{isset($followUpId) && $followUpId != '' && $v =='Document follow up' ? 'selected="selected"' :'' }} value="{{$v}}">{{$v}}</option>
                                        @endif
                                    @endforeach
                                @else
                                    @foreach($triageCallOutcome as $v)
                                        @if($attempt_count == 0 && $v != 'Attempt 2' && $v != 'Attempt 3')
                                            <option {{isset($followUpId) && $followUpId != '' && $v =='Document follow up' ? 'selected="selected"' :'' }} value="{{$v}}">{{$v}}</option>
                                        @elseif($attempt_count == 1 && $v != 'Attempt 1' && $v != 'Attempt 3')
                                            <option {{isset($followUpId) && $followUpId != '' && $v =='Document follow up' ? 'selected="selected"' :'' }} value="{{$v}}">{{$v}}</option>
                                        @elseif($attempt_count == 2 && $v != 'Attempt 1' && $v != 'Attempt 2')
                                            <option {{isset($followUpId) && $followUpId != '' && $v =='Document follow up' ? 'selected="selected"' :'' }} value="{{$v}}">{{$v}}</option>
                                        @endif
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        @if(LD::checkFeatureFlag('triage-added-ed-hospitalized-fields'))
                        <div class="col-md-6 hospitalized-and-ed-option hide">
                            <label class="w-100">&nbsp;</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="hospitalizedOption" />
                                <label class="form-check-label" for="hospitalizedOption">Currently Hospitalized</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="edOption" />
                                <label class="form-check-label" for="edOption">ED Visit</label>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @if(LD::checkFeatureFlag('edit-triage-call'))
                <div class="col-md-12 mt-3 <?php echo isset($followUpId) && $followUpId != '' ? '':'hide'?>" id="followupOutcomeForm">
                    @include('triage.followupFormOutcome',['originalCallDetails'=>$triage,'followUpDetails'=>isset($followUpDetails) ? $followUpDetails : '','followupData'=>$followupData,'followType'=>$followType])
                </div>
                @else
                <div class="col-md-12 mt-3 hide" id="followupOutcomeForm">
                    @include('triage.followupFormOutcome',['originalCallDetails'=>$triage,'followupData'=>$followupData,'followType'=>$followType])
                </div>
                @endif
                <div class="col-md-12 mt-3 hide" id="otherOutcomeForm">
                    <div class="form-group row">
                        <div class="col-md-12">
                            <label  class="w-100" for="triageCallOutcomeNotes">Notes</label>
                            <textarea class="form-control" name="triageCallOutcomeNotes" id="triageCallOutcomeNotes"></textarea>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-12 text-center mt-2">
                            <button class="btn btn-primary" onclick="saveNotesTriageFollowUp(event, this);">Save</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 d-flex p-0">
                <x-activity-log>

                </x-activity-log>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer">
    </x-slot>
</x-modal>


<x-modal>
    <x-slot name="id">order_details_form</x-slot>
    <x-slot name="size">w30</x-slot>
    <x-slot name="title">Add Order</x-slot>
    <x-slot name="class">order-details-form</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
        <form name="order-details-form" id="order-details-form">
            <div class="col-md-12 order-details-container">
                <div class="col-md-12">
                    <label class="w-100">Order Number</label>
                    <input type="text" class="form-control" required="true" name="order_number_input" id="order_number_input" value=""/>
                </div>
                <div class="col-md-12 pt-3">
                    <label class="w-100">Details</label>
                    <textarea name="order_details_input" id="order_details_input" required="true" class="form-control"></textarea>
                </div>

                <div class="col-md-12 pt-3">
                    <button class="btn btn-primary" type="button" onclick="saveOrderDetails(event, this);">Save</button>
                </div>
            <div>
        </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script>

$(document).off('change','.condition');
$(document).on('change','.condition',function(e){
    const id = $(this).attr('id');
    if($(this).val() == 'Worsened' || $(this).val() == 'Same'){
        const num = id.split('_')[1];
        $('div[data-parent-id="'+id+'"]').find('.condition-section').find('.additional-reason').remove();
        $('div[data-parent-id="'+id+'"]').find('.condition-section').append('<div class="additional-reason col-md-12 mt-2"><label for="">Additional Reason</label><textarea name="symptom[additionalReason]['+num+']" class="form-control"></textarea></div>');
    } else {
        $('div[data-parent-id="'+id+'"]').find('.condition-section').find('.additional-reason').remove();
    }
});

$(document).on("hidden.bs.modal", "#order_details_form", function() {
    $(".modal:visible").length && $(document.body).addClass("modal-open");
});

    function copyFormToClipboard(){
        var str = '';
        str += "\nSymptoms Status \n";
        $(".condition-section").each(function(ind,elm){
             if(typeof $(elm).find('select').attr('data-label') != 'undefined'){
                str += '\t'+$(elm).find('select').attr('data-label')+'\n';
                str += '\t\t'+'Condition: '+$(elm).find('select').val()+'\n';
                var notes = $(elm).find('textarea[name^="symptom[note]"]').val();
                if (typeof notes != 'undefined' && notes != '') {
                    str += '\t\t' + 'Notes: ' + notes + '\n';
                }
                var additionalReason = $(elm).find('textarea[name^="symptom[additionalReason]"]').val();
                if (typeof additionalReason != 'undefined' && additionalReason != '') {
                    str += '\t\t' + 'Additional Reason: ' + additionalReason + '\n';
                }
            }
        });
        $("#followUpTriageCallForm input[type=text],#followUpTriageCallForm input[type=time],#followUpTriageCallForm textarea,#followUpTriageCallForm select,#followUpTriageCallForm input[type=radio]:checked, #followUpTriageCallForm input[type=checkbox]:checked").not('.multiselect-container input[type=checkbox]:checked').each(function(ind,elm){
            //console.log($(elm).attr("name"));
            //console.log($(elm).siblings('label').html());

            if($(elm).attr("name")!='order_detail_table_length' && $(elm).attr("name") !== 'survay_sent') {
                if(typeof $(elm).siblings('label').html() != 'undefined' && $(elm).siblings('label').html() != ''){
                    if($(elm).siblings('label').html()=='Diagnosis') {
                        if(jsonTableData.length) {
                            //str+="Order Details\n";
                            for(var i=0;i<jsonTableData.length;i++) {
                                str+="Order "+jsonTableData[i].order_number+":"+jsonTableData[i].order_detail+'\n';
                            }
                        }
                    }
                    if("Additional Reason"!=$(elm).siblings('label').html() && "Additional care coordination services needed"!=$(elm).siblings('label').html()) {
                        let label = $(elm).siblings('label').html();
                        var value = $(elm).val();
                        if(label === "Yes" || label === "No") {
                            label = "Has Medication been picked up?";
                        }
                        if(value == 1){
                            value = 'Yes';
                        }else if(value == 0){
                            value = 'No';
                        }
                        str += label+': '+value + '\n';
                    }
                } else if(typeof $(elm).parent().parent().find('label').html() != 'undefined' && $(elm).parent().parent().find('label').html() != 'Additional Reason'){
                    var value = $(elm).val();
                    if(value == 1){
                        value = 'Yes';
                    }
                    str += $(elm).parent().parent().find('label').html()+': '+value + '\n';
                }
            }
        });



        //console.log(str);
        navigator.clipboard.writeText(str).then(function() {
            console.log('Copied!');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
    function loadTriageCallOutcome(outcome){
        var followUpType = $('#triageCallOutcome').attr('data');
        console.log(followUpType,'foll');
        @if(LD::checkFeatureFlag('triage-added-ed-hospitalized-fields'))
            if(outcome != '' && outcome == 'Document follow up'){
                $("#followupOutcomeForm").removeClass('hide');
                $("#otherOutcomeForm").addClass('hide');
            }else if( (outcome != '' && outcome.indexOf('Attempt 1') != -1 && followUpType == '1D') || (outcome != '' && outcome.indexOf('Attempt 3') != -1 && followUpType == '2D') || (outcome != '' && outcome.indexOf('Attempt 3') != -1 && followUpType == '7D') || (outcome != '' && outcome.indexOf('Attempt 3') != -1 && followUpType == '30D')){
                $("#otherOutcomeForm").removeClass('hide');
                $(".hospitalized-and-ed-option").removeClass('hide');
                $("#followupOutcomeForm").addClass('hide');
            }else if(outcome != ''){
                $("#otherOutcomeForm").removeClass('hide');
                $("#followupOutcomeForm").addClass('hide');
                $(".hospitalized-and-ed-option").addClass('hide');
            }else{
                $("#otherOutcomeForm").addClass('hide');
                $("#followupOutcomeForm").addClass('hide');
                $(".hospitalized-and-ed-option").addClass('hide');
            }
        @else
            if(outcome != '' && outcome == 'Document follow up'){
                $("#followupOutcomeForm").removeClass('hide');
                $("#otherOutcomeForm").addClass('hide');
            }else if(outcome != ''){
                $("#otherOutcomeForm").removeClass('hide');
                $("#followupOutcomeForm").addClass('hide');
            }else{
                $("#otherOutcomeForm").addClass('hide');
                $("#followupOutcomeForm").addClass('hide');
            }
        @endif
    }
    $(document).on('change','#triageCallOutcome',function(e){
        loadTriageCallOutcome($(this).val());
    });
    $("#followUpTriageCallForm").validate({
        ignore: [],
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "finalDisposition":{
                required:true,
            },
            // "despositionActionDate":{
            //     required:true,
            // },
            "hospitalizationStartDate":{
                required: function(element) {
                    return ($("#wasClientHospitalized").is(":checked"));
                }
            },
            "drugClassification":{
                required: function(element) {
                    return ($("#wasMedPrescribed").is(":checked"));
                }
            },
            "hasMedicationPickedUp":{
                required: function(element) {
                    return ($("#wasMedPrescribed").is(":checked"));
                }
            },
            "interventionType":{
                required: function(element) {
                    return ($("#wereNewOrderRecieived").is(":checked"));
                }
            },
            "diagnosis":{
                required: function(element) {
                    return ($("#wasDiagnosisMade").is(":checked"));
                }
            }
        }
    });

    function openOrderForm(e,button) {
        $('#order_details_form').modal('show');
    }



    @if(LD::checkFeatureFlag('edit-triage-call'))
        var jsonTableData = '<?php echo isset($followUpDetails->orderDetails) ? addslashes($followUpDetails->orderDetails) : ""?>';
        jsonTableData = jsonTableData != '' ? JSON.parse(jsonTableData) : [];
        var jsonHiddenAddedData = JSON.parse(JSON.stringify(jsonTableData));
    @else
        var jsonTableData = [];
        var jsonHiddenAddedData = [];
    @endif


    if(!$.fn.dataTable.isDataTable('#order_detail_table')){
        var orderDetailTable =  $('#order_detail_table').dataTable({
            data: jsonTableData,
            columns: [
                { data: 'order_number', width:'10%' },
                { data: 'order_detail',width:'70%' },
                { data: 'order_date', width:'20%' }
            ]
        });
    }

    function saveOrderDetails(e,button){

        console.log("caled");
        if($('#order-details-form').valid()){
            var orderNumber = $("#order_number_input").val();
            var orderDetails = $("#order_details_input").val();
            $("#order_number_input").val("");
            $("#order_details_input").val("");

            var currentdate = new Date();
            var datetime =("0" + (currentdate.getMonth()+1)).slice(-2)  + "/"+ ("0" + currentdate.getDate()).slice(-2) + "/" + currentdate.getFullYear() + " " + ("0" + currentdate.getHours()).slice(-2) + ":" + ("0" + currentdate.getMinutes()).slice(-2) + ":" + ("0" +currentdate.getSeconds()).slice(-2);
            var item = {}
            item["order_number"] = orderNumber;
            item["order_detail"] = orderDetails;
            item["order_date"] = datetime;
            jsonTableData.push(item);
            jsonHiddenAddedData.push(item);
            $('#order_detail_table').DataTable().clear().draw();
            $('#order_detail_table').DataTable().rows.add(jsonTableData); // Add new data
            $('#order_detail_table').DataTable().columns.adjust().draw(); // Redraw the DataTable
            $('#order_details_form').modal('hide');
        }
    }

    function saveNotesTriageFollowUp(e, button){
        e.preventDefault();
        if($('#triageCallOutcomeNotes').val() != ''){
            $('.disabled').removeAttr('disabled');
            button.disabled = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            @if(LD::checkFeatureFlag('triage-added-ed-hospitalized-fields'))
                var data = {notes:$('#triageCallOutcomeNotes').val(),'triagecall_id':$('#triagecall_id').val(),'followupType':$('#followupType').val(),outcome:$('#triageCallOutcome').val(),isHospitalized:$('#hospitalizedOption').is(':checked'),isEd:$('#edOption').is(':checked')};
            @else
                var data = {notes:$('#triageCallOutcomeNotes').val(),'triagecall_id':$('#triagecall_id').val(),'followupType':$('#followupType').val(),outcome:$('#triageCallOutcome').val()};
            @endif
            data['_token'] = _token;
            $.ajax({
                url: "{{route('triagefollowup.saveNotesTriageFollowUp')}}",
                type: 'post',
                data: data,
                success: function(data) {
                    button.disabled = false;
                    if(data.status){
                        isConfirmed = 1;
                        isSave = 1;
                        $('#triageOutcomeDetailsModal').modal('hide');
                        $('#upcoming_calls .dataTable').DataTable().ajax.reload(null, false);
                    }else{
                        console.log('Error:' + data.msg);
                    }
                    $('.disabled').attr('disabled');
                }
            });
        }else{
            $('#triageCallOutcomeNotes').parent().append('<label class="error">This field is required.</label>')
        }
    }
    function saveTriageFollowUp(e, button){
        e.preventDefault();
        console.log(jsonHiddenAddedData,'jsonHiddenAddedData');
        if($('#followUpTriageCallForm').valid()){
            $('.disabled').removeAttr('disabled');
            button.disabled = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#followUpTriageCallForm').serializeObject();
            data['outcome'] = $('#triageCallOutcome').val();
            data['orderdetails'] = jsonHiddenAddedData;
            data['_token'] = _token;
            $.ajax({
                url: "{{route('triagefollowup.save')}}",
                type: 'post',
                data: data,
                success: function(data) {
                    button.disabled = false;
                    if(data.status){
                        isConfirmed = 1;
                        isSave = 1;
                        <?php if(LD::checkFeatureFlag('triage-details-edit-call')){ ?>
                            $('.modal').modal('hide');
                        <?php }else{ ?>
                            $('#triageOutcomeDetailsModal').modal('hide');
                        <?php } ?>
                        $('#upcoming_calls .dataTable').DataTable().ajax.reload(null, false);

                    }else{
                        console.log('Error:' + data.msg);
                    }
                    $('.disabled').attr('disabled');
                }
            });
        }
    }
    $(document).on('change','#wasClientHospitalized,#wasMedPrescribed,#wereNewOrderRecieived, #wasDiagnosisMade',function(){

        if($(this).is(":checked")){
           $("."+$(this).attr("id")).removeClass("hide");
        } else {
            if($(this).attr("id") == 'wereNewOrderRecieived' && $('#wasMedPrescribed').is(":checked")){
                $("."+$(this).attr("id")).removeClass("hide");
            }else if($(this).attr("id") == 'wasMedPrescribed' && $('#wereNewOrderRecieived').is(":checked")){
                $("."+$(this).attr("id")).removeClass("hide");
            }else{
                $("."+$(this).attr("id")).addClass("hide");
            }
        }

    });
    function reloadTriageActivity(id, type, destination) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('triage.getTriageActivity')}}",
            type: 'get',
            data: {
                id: id,
                type: type,
                _token: _token
            },
            dataType: 'html',
            success: function(data) {
                $(destination).html(data);
            }
        });
    }
    $(document).ready(function() {

        $('#followUpTriageCallForm #additionCoordination').multiselect({
            includeSelectAllOption: true,
            onSelectAll:function(){
                $("#additionalCoordinationOther").removeClass("hide");
            },
            onDeselectAll:function(){
                $("#additionalCoordinationOther").addClass("hide");
            },
            onChange: function(option, checked, select) {
                $('#followUpTriageCallForm #additionCoordination option:selected').each(function() {
                    if($(this).val().toLowerCase().indexOf('other') != -1){
                        $("#additionalCoordinationOther").removeClass("hide");
                    }else{
                        $("#additionalCoordinationOther").addClass("hide");
                    }
                });
                if($('#followUpTriageCallForm #additionCoordination option:selected').length <=0 && (option).val() == 'Other' && !checked){
                    $("#additionalCoordinationOther").addClass("hide");
                }
            },
            includeSelectAllOption: true, enableCaseInsensitiveFiltering: true
        });
        $('#followUpTriageCallForm #followUpAction').multiselect({includeSelectAllOption: true});

        reloadTriageActivity('<?php echo $triage->id?>', 'triage', '.activity-log');

        $('#followUpTriageCallForm #despositionActionDate,#followUpTriageCallForm #hospitalizationStartDate,#followUpTriageCallForm #hospitalizationEndDate').datetimepicker({
            format: 'Y-m-d H:i:s',
            lang: 'en',
        });

        $(".condition").each(function(){
            $("#"+$(this).attr("id")).rules("add", {
                required: true,
            });
        });

    });


</script>
