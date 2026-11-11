<?php
    use App\Helpers\H;
    use Illuminate\Support\Facades\Auth;
    $mod = isset($mod) && $mod != '' ? $mod : '';
?>

<x-modal>
    <x-slot name="id">addSuppliesRequestFormModal</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">Supplies Request</x-slot>
    <x-slot name="class">addSuppliesRequest</x-slot>
    <x-slot name="body">
        <form id="addSuppliesRequestForm" class="addSuppliesRequestForm">
            @if(isset($mod) && $mod == 'Edit')
            <input type="hidden" name="id" id='id' value="{{$supplyDetails['id']}}" />
            @endif
            <p style="color: red;" class="supplyerror" id="supplyerror">
            Something went wrong. Please close the form and try again.</p>
            <section class="supplies-details">
                @if((isset($mod) && $mod == 'Edit') || (isset($isOutcomeForm) && $isOutcomeForm) )
                    <div class="form-group row">
                        <div class="col-md-4">
                            <label  class="w-100" for="aoc_patient_id">Date requested</label>
                            <input type="text" class="form-control" id="created_at" {{($isViewOnly) ? 'disabled' : ''}} name="created_at" value="{{isset($supplyDetails['created_at']) ? H::getFormatedDateTime($supplyDetails['created_at']) : ''}}" readonly='true'/>
                        </div>
                        <div class="col-md-4">
                            <label  class="w-100" for="aoc_patient_id">Date fulfilled</label>
                            <input type="text" class="form-control" id="completed_at" {{$isViewOnly ? 'disabled' : ''}} name="completed_at" value="{{isset($supplyDetails['completed_at']) ? H::getFormatedDateTime($supplyDetails['completed_at']) : ''}}" readonly='true' />
                        </div>
                        <div class="col-md-4">
                            <label  class="w-100" for="aoc_patient_id">Fulfilled by</label>
                            <input type="text" class="form-control" id="completed_by" {{$isViewOnly ? 'disabled' : ''}} name="completed_by" value="{{isset($supplyDetails['completed_by']) ? H::getUserNameById($supplyDetails['completed_by']) : ''}}" readonly='true' />
                        </div>
                    </div>
                @endif
                <p id="orderAlert" class="form-text text-danger">Alert: Client has ordered in past 30 days</p>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label  class="w-100" for="aoc_patient_id">Client Name</label>
                        <input type="text" class="form-control" id="clientName" {{($isViewOnly || $mod != 'Add') ? 'disabled' : ''}} name="clientName" value="{{isset($patientDetails->Patient_FirstName) ? $patientDetails->Patient_FirstName.' '.$patientDetails->Patient_LastName : ''}}"/>
                    </div>
                    <div class="col-md-6">
                        <label  class="w-100" for="aoc_patient_id">Client ID</label>
                        <input type="text" class="form-control" id="aoc_patient_id" {{$isViewOnly ? 'disabled' : ''}} name="aoc_patient_id" value="{{isset($supplyDetails['aoc_patient_id']) ? $supplyDetails['aoc_patient_id'] : ''}}" readonly='true' />
                    </div>
                </div>
            </section>

            @if ($isViewOnly || $mod == 'Edit')
            @foreach($items as $k => $v)
            @if($k < 1)
            <div class="supplies-form-inner mt-5">
            @else
            <div class="supplies-form-inner mt-3">
            @endif
                <div class="form-group row">
                    <div class="col-md-4">
                        @if ($k < 1)
                            <label class="w-100">Item</label>
                        @endif
                        <select name="item{{$k}}" class="form-control" {{$mod != 'Edit' ? 'disabled' : ''}}>
                        <option value="">--- Select ---</option>
                        @foreach(config('constant')['suppliesItems'] as $vv)
                        <option {{$v['item'] == $vv ? 'selected="selected"' : ''}} value="{{$vv}}">{{$vv}}</option>
                        @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        @if ($k < 1)
                            <label class="w-100">Description</label>
                        @endif
                        <input name="description{{$k}}" type="text" class="form-control"
                            {{$mod != 'Edit' ? 'disabled' : ''}}
                            value="{{$v['description']}}"/>
                    </div>
                    <div class="col-md-3">
                        @if ($k < 1)
                            <label class="w-100">Qty</label>
                        @endif
                        <input name="qty{{$k}}" type="text" class="form-control"
                        {{$mod != 'Edit' ? 'disabled' : ''}}
                        value="{{$v['qty']}}"/>
                    </div>
                    @if ($mod == 'Edit')
                    <div class="col-md-1 removebtn d-flex">
                        <a href="javascript:void(0);" class='supplies-remove-icon align-self-end'>
                            <i class="fa fa-times-circle-o"></i>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
            @endif
            @if ($mod == 'Edit' || $mod == 'Add')
            <section class="supplies-items-details">
                <div class="supplies-items-cnt"></div>
                <a href="javascript:void(0);" onclick="loadMoreSuppliesItemForm()" id="add_more">Add More</a>
            </section>
            <div class="form-group row">
                <label for="notes" class="col-md-12">Notes</label>
                <div class="col-md-12">
                    <textarea
                    name="notes"
                    id="notes"
                    class="form-control"
                    rows="2">{{isset($supplyDetails['notes']) ? trim($supplyDetails['notes']) : NULL}}</textarea>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-12 text-center mt-2">
                    <button class="btn btn-primary" onclick="saveSupplies(event, this);">Submit</button>
                </div>
            </div>
            @else
            <div class="form-group row">
                <label for="notes" class="col-md-12">Notes</label>
                <div class="col-md-12">
                    <textarea
                    name="notes"
                    id="notes"
                    class="form-control"
                    rows="2"
                    disabled
                    >{{isset($supplyDetails['notes']) ? trim($supplyDetails['notes']) : NULL}}</textarea>
                </div>
            </div>
            @endif
        </form>
        <!-- Outcome list to change -->
        @if (isset($isOutcomeForm) && $isOutcomeForm)
        <form id="outcomeSuppliesRequestForm" class="outcomeSuppliesRequestForm">
            <input type="hidden"
                id="supplyRequestId"
                name="supplyRequestId"
                value="{{isset($supplyDetails['id']) && $supplyDetails['id'] ? $supplyDetails['id'] : ''}}">
            <div class="form-group row">
                <div class="col-md-6">
                    <label class="w-100">Outcome</label>
                    <select class="form-control" name="status" id="status">
                        <option value="">-- Select One --</option>
                        @if (isset($supplyDetails['status']) && $supplyDetails['status'] != 1)
                        <option value="1">Supplies Ordered</option>
                        @endif
                        <option value="2">Supplies Delivered</option>
                    </select>
                </div>
                <div class="col-md-6 text-left mt-2 align-self-end">
                    <button class="btn btn-primary" onclick="saveOutcomeForRequestSupplies(event, this);">Save</button>
                </div>
            </div>
        </form>
        @endif
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<x-modal>
    <x-slot name="id">validateSupplyRequestsForSelectedClient</x-slot>
    <x-slot name="size">w-50</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="isCloseBtn">false</x-slot>
    <x-slot name="title">Add Supply Requests</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <h5>Client has an active supply request.  Would you like to edit the current request?</h5>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-primary"
        id="validateSupplyRequestsForSelectedClientYesBtn" data-id="">
            Yes
        </button>
        <button type="button" class="btn btn-primary" id="validateSupplyRequestsForSelectedClientNoBtn">
            No
        </button>
    </x-slot>
</x-modal>

<script>
    $("#supplyerror").hide();
    $("#addSuppliesRequestForm #clientName").autocomplete({
        source: "{{ route('getScheduleClient') }}",
        classes: {
            "ui-autocomplete": "clientName"
        },
        minLength: 2,
        select: function(event, ui) {
            $('#addSuppliesRequestForm #aoc_patient_id').val(ui.item.id);
            validateSupplyRequestsForSelectedClient(ui.item.id);
        },
        change: function(event, ui) {
            if (!ui.item) {
                $('#addSuppliesRequestForm #clientName').val('');
                $('#addSuppliesRequestForm #aoc_patient_id').val('');
            }
        }
    });
    function validateSupplyRequestsForSelectedClient(clientId){
        let _token = $('meta[name="csrf-token"]').attr('content');
        checkCompleted(clientId);
        $.ajax({
            url: "{{route('supplies.validateSupplyRequestsForSelectedClient')}}",
            type: 'get',
            data: {
                clientId:clientId,
                _token: _token
            },
            success: function(data) {
                console.log(data,'data');
                if(data.status == true && data.supplyId != ''){
                    $('#validateSupplyRequestsForSelectedClientYesBtn').attr('data-id',data.supplyId);
                    $('#validateSupplyRequestsForSelectedClient').modal('show');
                }
            }
        });
    }
    $('#validateSupplyRequestsForSelectedClientYesBtn').click(function(){
        if($(this).attr('data-id') != ''){
            editSupplyRequests($(this).attr('data-id'));
            $('.modal').modal('hide');
            $('.modal-backdrop').remove();
        }
    });
    $('#validateSupplyRequestsForSelectedClientNoBtn').click(function(){
        $('.modal').modal('hide');
    });
    $(document).on("hidden.bs.modal", "#validateSupplyRequestsForSelectedClient", function() {
            $(".modal:visible").length && $(document.body).addClass("modal-open");
        });
    @if ($mod == 'Add')
        var count = 0;
    @else
    var count = {{count($items)}};
    @endif

    function loadMoreSuppliesItemForm(){
        count++;
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('supplies.loadMoreSuppliesItemForm')}}",
            type: 'get',
            dateType:"html",
            data: {
                count:count,
                _token: _token
            },
            success: function(data) {
                $('.supplies-items-cnt').append(data);
            }
        });
    }
    $(document).ready(function(){
        @if ($mod != 'Edit')
            loadMoreSuppliesItemForm();
        @endif
    });
    $(document).on('click','.supplies-remove-icon',function(){
        $(this).parent().parent().remove();
        //count--;
    });
    function saveSupplies(e, button){
        e.preventDefault();
        if($('#addSuppliesRequestForm').valid()){
            button.disabled = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#addSuppliesRequestForm').serializeObject();
            data['_token'] = _token;
            data['count'] = count;
            $.ajax({
                url: "{{route('supplies.save')}}",
                type: 'post',
                data: data,
                success: function(data) {
                    button.disabled = false;
                    if(data.status){
                        $('#addSuppliesRequestFormModal').modal('hide');
                        $('.dataTable').DataTable().ajax.reload(null, false);
                    }else{
                        $("#supplyerror").show();
                        console.log('Error:' + data.msg);
                    }
                }
            });
        }
    }

    $("#outcomeSuppliesRequestForm").validate({
        ignore: [],
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "status":{
                required: true
            },
        }
    });
    function saveOutcomeForRequestSupplies(e, button){
        e.preventDefault();
        if($('#outcomeSuppliesRequestForm').valid()){
            button.disabled = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#outcomeSuppliesRequestForm').serializeObject();
            data['_token'] = _token;
            $.ajax({
                url: "{{route('supplies.saveOutcomeForRequestSupplies')}}",
                type: 'post',
                data: data,
                success: function(data) {
                    button.disabled = false;
                    if(data.status){
                        $('#addSuppliesRequestFormModal').modal('hide');
                        $('.dataTable').DataTable().ajax.reload(null, false);
                    }else{
                        console.log('Error:' + data.msg);
                    }
                }
            });
        }
    }
    $('#status').change(function() {
        var currentDate = new Date();
        var hours = currentDate.getHours() % 12 || 12; // Convert to 12-hour format
        var amPm = currentDate.getHours() >= 12 ? 'PM' : 'AM';
        var formattedDate = (currentDate.getMonth() + 1).toString().padStart(2, '0') + '/' +
            currentDate.getDate().toString().padStart(2, '0') + '/' +
            currentDate.getFullYear().toString().slice(-2) + ' ' +
            hours.toString().padStart(2, '0') + ':' +
            currentDate.getMinutes().toString().padStart(2, '0') + ' ' + amPm;

        $('#completed_at').val(formattedDate);
        $('#completed_by').val('{{Auth::user()->name}}');
    });
    $("#orderAlert").hide();
    function checkCompleted(id){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('supplies.checkCompleted')}}",
            type: 'get',
            data: {
                clientId:id,
                _token: _token
            },
            success: function(data) {
                console.log(data,'data');
                if(data.status == true){
                    $("#orderAlert").show();
                }else{
                    $("#orderAlert").hide();
                }
            }
        });
    }
</script>
