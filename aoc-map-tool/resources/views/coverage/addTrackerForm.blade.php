<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $approvedByList = H::getHelper('ConstantHelper')->getUsersByRoles(['Client Care Coordinator','Coverage Coordinator','Client Care Manager','Director of Branch Operations','Regional Director of Operations','State VP','Executive Team']);
?>

<x-modal>
    <x-slot name="id">addCoverageTrackerFormModal</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">Add to Coverage Tracker</x-slot>
    <x-slot name="class">add-coverage-tracker-form</x-slot>
    <x-slot name="body">
    <form id="addCoverageTrackerForm" class="addCoverageTrackerForm">
        <section class="triage-details">
            <div class="alert alert-danger mt-3 clinicianValidationMsg hide" role="alert">You've assigned and RN to a non RN shift.</div>
            <input type="hidden" class="form-control" id="selectedCoverageRows" name="selectedCoverageRows" value=""/>
            <div class="form-group row">
                <div class="col-md-6">
                    <label  class="w-100" for="candidateName">Clinician</label>
                    <input type="hidden" class="form-control" id="aoc_provider_id" name="aoc_provider_id" value=""/>
                    <input type="text" class="form-control" id="candidateName" name="candidateName" value=""/>
                </div>
                <div class="col-md-6">
                    <label  class="w-100" for="escalated_pay_rate">Escalated Pay Rate</label>
                    <input type="input" class="form-control float" id="escalated_pay_rate" name="escalated_pay_rate" value="">
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-6">
                    <label class="w-100" for="approved_by">Approved By</label>
                    <select id="approved_by" name="approved_by" class="form-control"
                        autocomplete="off">
                        <option value="">-- Select One --</option>
                        @foreach ($approvedByList as $i => $item)
                        <option value="{{$item['text']}}">{{$item['text']}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label  class="w-100" for="gp">GP %</label>
                    <input type="input" class="form-control float" id="gp" name="gp" value="">
                </div>
            </div>
        </section>
        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <button class="btn btn-primary" onclick="saveCoverageTrackerForm(event, this);">Save</button>
            </div>
        </div>
    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script>
    $("#candidateName").autocomplete({
        source: function(request, response) {
            $.getJSON("{{ route('getCandidateName') }}", {
                status: 'current',
                term: $('#candidateName').val()
            }, response);
        },
        minLength: 2,
        classes: {
            "ui-autocomplete": "clientName"
        },
        select: function(event, ui) {
            $('#aoc_provider_id').val(ui.item.id);
            validateDisciplineName(ui.item.id);
        }
    });
    function validateDisciplineName(providerId){
        let _token = $('meta[name="csrf-token"]').attr('content');
        coverageMapRowIds = $("#selectedCoverageRows").val();
        $.ajax({
            url: "{{route('coverage.validateDisciplineName')}}",
            type: 'get',
            dateType:"html",
            data: {
                providerId:providerId,
                coverageMapRowIds:coverageMapRowIds,
                _token: _token
            },
            success: function(data) {
                if(!data.status){
                    $(".clinicianValidationMsg").removeClass('hide');
                }else{
                    $(".clinicianValidationMsg").addClass('hide');
                }
            }
        });
    }
    $("#addCoverageTrackerForm").validate({
        ignore: [],
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "escalated_pay_rate":{
                required: true
            },
            "approved_by":{
                required: true
            },
            "gp":{
                required: true
            },
            "candidateName":{
                required: function(element) {
                    return !($("#aoc_provider_id").val() != '');
                }
            },
        }
    });
    function saveCoverageTrackerForm(e, button){
        e.preventDefault();
        if($('#addCoverageTrackerForm').valid()){
            button.disabled = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#addCoverageTrackerForm').serializeObject();
            data['_token'] = _token;
            $.ajax({
                url: "{{route('coverage.save')}}",
                type: 'post',
                data: data,
                success: function(data) {
                    button.disabled = false;
                    if(data.status){
                        $('#addCoverageTrackerFormModal').modal('hide');
                        $('.dataTable').DataTable().ajax.reload(null, false);
                        $("#addCoverageTracker").addClass('hide');
                    }else{
                        console.log('Error:' + data.msg);
                    }
                }
            });
        }
    }
</script>
