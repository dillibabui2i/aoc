<?php
    use App\Helpers\H;
    use App\Helpers\LD;
?>
<style>
    .w95{
        max-width: 95% !important;
    }
    .edit-triage-call-form,.edit-triage-date{
        padding: 2px;
        font-size: 11px;
        height: fit-content;
    }
    #notes{
        overflow-y: auto;
    }
</style>
<link href="{{ mix('css/jquery.datetimepicker.min.css') }}" rel="stylesheet">
<script src="{{ mix('js/jquery.datetimepicker.full.min.js') }}"></script>

<x-modal>
    <x-slot name="id">triageCallsDetailModal</x-slot>
    <x-slot name="size">w95</x-slot>
    <x-slot name="title">Triage Calls /
        {{(isset($patientsDetails->Patient_FirstName) ? $patientsDetails->Patient_FirstName : '').' '.(isset($patientsDetails->Patient_LastName) ? $patientsDetails->Patient_LastName : '')}}
    </x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            <?php if(LD::checkFeatureFlag('add-sticky-note-icon-to-triage-call-page')) { ?>
            <a data-bs-dismiss="modal" href="javascript:void(0);"
                onclick="event.preventDefault();openNotes('{{$triage->id}}','triage','Triage Notes Added')"><i
                    class="fa  fa-sticky-note-o"></i></a>
            <?php } ?>

        </div>
    </x-slot>
    <x-slot name="class">triageCallsDetailModal</x-slot>
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

                                <p class="card-text mt-2">
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
                            <h5 class="card-title bg-blue">Call History</h5>
                            <div class="detail-section">
                                <p class="card-text">
                                    <strong>{{$countArray['openCall']}}</strong> Open Calls
                                </p>
                                <p class="card-text">
                                    <strong>{{$countArray['bodySystem']}}</strong> Body Systems Affected
                                </p>
                                <p class="card-text">
                                    <strong>{{$countArray['disposition']}}</strong> Urgent Dispositions
                                </p>
                                <p class="card-text">
                                    <strong>{{$countArray['ambulance']}}</strong> Ambulance Transfer
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 p-0 d-flex mt-4">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable-triage-details-grid" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <?php foreach($dataTableFields as $key => $data) {?>
                                        <th class="bg-blue"><?php echo $data['label']?></th>
                                    <?php }  ?>
                                    <th class="bg-blue">Action</th>
                                </tr>
                            </thead>
                        </table>
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

<script>
    var lastCollapsedTr;
    $(document).ready(function() {

        var fields = '<?php echo json_encode($dataTableFields);?>';
        fields = JSON.parse(fields);
        var columns = [];
        $.each(fields, function(key, val) {
            columns.push({
                data: val.name,
                name: val.name,
                visible: typeof val.visible !== 'undefined' ? val.visible : true,
                width: typeof val.width !== 'undefined' ? val.width : ''
            });
        });
        var triageTable;
        $(document).ready(function($) {
            var dataTableDelay = 1000;
            columns.push({
                data: null,
                width: '5%',
                orderable: false,
                className:'openCallDetailsAction text-center',
                render: function(data, type, row) {
                    return '<i class="fa fa-angle-right" aria-hidden="true"></i>';
                }
            });
            triageTable = $('#dataTable-triage-details-grid').DataTable({
                "order": [
                    [1, "desc"]
                ],
                "ajax": {
                    "url": "{{ route('triage.getTriageCallOfClient') }}?clientId={{$patientsDetails->id}}",
                    data: function(d) {
                        d.filters = $('#datatableFilterSearchForm').serializeArray();
                    }
                },

                "processing": true,
                "serverSide": true,
                "initComplete": function(settings, json) {
                    jQuery('#dataTable_filter input').off('keyup.DT input.DT');
                    var searchDelay = null;
                    jQuery('#dataTable_filter input').on('keyup', function(e) {
                        var search = jQuery('#dataTable_filter input').val();
                        clearTimeout(searchDelay);
                        searchDelay = setTimeout(function() {
                            if (search != null && search.length >= 3)
                                table.search(search).draw();
                            if (search.length == 0 && e.keyCode == 8)
                                table.search(search).draw();
                        }, dataTableDelay);
                    });
                },
                "columns": columns
            });
        });

        function collapseAllRow(){
            console.log('here');
            var row = triageTable.row(lastCollapsedTr);
            if(row.child.isShown()){
                $(lastCollapsedTr).find('td.openCallDetailsAction i').removeClass('fa-angle-down');
                $(lastCollapsedTr).find('td.openCallDetailsAction i').addClass('fa-angle-right');
                row.child.hide();
                lastCollapsedTr.removeClass('shown');
            }
        }
        $(document).off('click', '#dataTable-triage-details-grid tbody td.openCallDetailsAction');
        $(document).on('click', '#dataTable-triage-details-grid tbody td.openCallDetailsAction', function () {
            var tr = $(this).closest('tr');
            var row = triageTable.row(tr);
            console.log(row,'row');
            if (row.child.isShown()) {
                $(this).find('i').removeClass('fa-angle-down');
                $(this).find('i').addClass('fa-angle-right');
                row.child.hide();
                tr.removeClass('shown');
            } else {
                collapseAllRow();
                lastCollapsedTr = tr;
                $(this).find('i').removeClass('fa-angle-right');
                $(this).find('i').addClass('fa-angle-down');
                console.log(row.data(),'row.data()');

                <?php if(LD::checkFeatureFlag('triage-details-edit-call')){ ?>
                    getTriageEditForm(row.data(),function(res){
                        row.child(res).show();
                        tr.addClass('shown');
                        $('a[data-triage-type="original"]').trigger('click');
                    });
                <?php } else { ?>
                    getFollowUpCallDetails(row.data(),function(res){
                        row.child(res).show();
                        tr.addClass('shown');
                    });
                <?php }?>
            }
        });
        function getFollowUpCallDetails(d,cb) {
            // let _token = $('meta[name="csrf-token"]').attr('content');
            // d['_token'] = _token;
            $.ajax({
                url: "{{route('triage.getFollowUpCallDetails')}}",
                type: 'get',
                dateType:"html",
                data:d,
                success: function(data) {
                    cb(data);
                }
            });
        }
        function getTriageEditForm(d,cb) {
            // let _token = $('meta[name="csrf-token"]').attr('content');
            // d['_token'] = _token;
            $.ajax({
                url: "{{route('triage.getTriageEditForm')}}",
                type: 'get',
                dateType:"html",
                data:d,
                success: function(data) {
                    cb(data);
                }
            });
        }

    });

    function loadTriageEditForm(id,type,followupType,cb){
        $.ajax({
            url: "{{route('triage.loadTriageEditForm')}}",
            type: 'get',
            dateType:"html",
            data:{id:id,type:type,followupType:followupType},
            success: function(data) {
                cb(data);
            }
        });
    }
    $(document).on('click','.edit-triage-call-form',function(){
        $('.follow-type-tabs .tab-content').find('select,radio,checkbox,input,textarea,button.multiselect,.wereNewOrderRecieived button,button.btn').not('.disabled').removeAttr('disabled');
        $('#bodySystem').enable();
    });

    @if(H::canPermissions(['edit-triage-call-date']))
        $(document).on('click','.edit-triage-date',function(){
            $("#changeTriageDateTime").modal('show');
            $('#changeTriageDateTime').find('select,radio,checkbox,input,textarea,button.multiselect,.wereNewOrderRecieived button,button.btn').not('.disabled').removeAttr('disabled');
        });
        $("#changeTriageDateTimeForm").validate({
            ignore: [],
            onfocusout: false,
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
                "callDate":{
                    required: true
                },
                "callStartTime":{
                    required: true
                }
            }
        });
        $(document).off('click','#saveChangeTriageDateTime');
        $(document).on('click','#saveChangeTriageDateTime',function(){
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#changeTriageDateTimeForm').serializeObject();
            data['_token'] = _token;
            $.ajax({
                url: "{{route('triage.editTriageCallDate')}}",
                type: 'get',
                data: data,
                dataType: 'html',
                success: function(data) {
                    $("#changeTriageDateTime").modal('hide');
                    $('#myTab .triage-edit-form[data-triage-type="original"]').trigger('click');
                }
            });
        });
        $(document).on('click','#cancelChangeTriageDateTime',function(){
            $("#changeTriageDateTime").modal('hide');
        });
        $(document).on("hidden.bs.modal", "#changeTriageDateTime", function() {
            $(".modal:visible").length && $(document.body).addClass("modal-open");
            $('.modal-backdrop').remove();
        });
    @endif


    $(document).off('click','.triage-edit-form');
    $(document).on('click','.triage-edit-form',function(){
        if($(this).attr('data-triage-type') == 'original'){
            loadTriageEditForm($(this).attr('data-id'),'original','',function(res){
                // $('.follow-type-tabs .tab-content').html($(res).find('.modal-body').html());
                $('.follow-type-tabs .tab-content').html('');
                $editStr = '<div class="d-flex"><h3>Triage Incoming Call</h3><a class="edit-triage-call-form ml-3 btn btn-primary" href="javascript:void(0);">Edit Triage Call</a>';
                @if(H::canPermissions(['edit-triage-call-date']))
                    $editStr += '<a class="edit-triage-date ml-3 btn btn-primary" href="javascript:void(0);">Edit Triage Date</a></div>';
                @else
                    $editStr += '</div>';
                @endif

                $('.follow-type-tabs .tab-content').html($editStr);
                const formHtml = $(res).find('#addTriageCallForm');
                const script = $(res).find('script');
                const orderModal = $(res).find('#order_details_form');
                const changeTriageDateTime = $(res).find('#changeTriageDateTime');
                formHtml.appendTo('.follow-type-tabs .tab-content');
                $('.follow-type-tabs .tab-content').find('#addTriageCallForm').prepend('<input type="hidden" value="Document follow up" name="outcome" />');
                orderModal.appendTo('.follow-type-tabs .tab-content');
                changeTriageDateTime.appendTo('.follow-type-tabs .tab-content');
                script.appendTo('.follow-type-tabs .tab-content');

                //Making the input disable
                $('.follow-type-tabs .tab-content').find('select,radio,checkbox,input,textarea,button.multiselect,.wereNewOrderRecieived button,button.btn').not('select[name="bodySystem"]').attr('disabled',true);
            });
        }else{
            loadTriageEditForm($(this).attr('data-id'),'followup',$(this).attr('data-followup-type'),function(res){
                $('.follow-type-tabs .tab-content').html('');
                $('.follow-type-tabs .tab-content').html('<div class="d-flex"><h3>Triage Followup</h3><a class="edit-triage-call-form ml-3" href="javascript:void(0);"><i class="fa fa-pencil"></i></a></div>');
                const formHtml = $(res).find('#followupOutcomeForm');
                const script = $(res).find('script');
                const orderModal = $(res).find('#order_details_form');
                formHtml.appendTo('.follow-type-tabs .tab-content');
                setTimeout(function(){
                    $('.follow-type-tabs .tab-content').find('#followupOutcomeForm form').prepend('<input type="hidden" value="Document follow up" name="outcome" id="triageCallOutcome" />');
                },100);
                orderModal.appendTo('.follow-type-tabs .tab-content');
                script.appendTo('.follow-type-tabs .tab-content');
                //Making the input disable
                $('.follow-type-tabs .tab-content').find('select,radio,checkbox,input,textarea,button.multiselect,.wereNewOrderRecieived button,button.btn').not('select[name="bodySystem"]').attr('disabled',true);
            });
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
        reloadTriageActivity('<?php echo $triage->id?>', 'triage', '.activity-log');
    });
    $(document).on("hidden.bs.modal", "#notes-popup", function() {
        reloadTriageActivity('<?php echo $triage->id?>', 'triage', '.activity-log');
    });
</script>
