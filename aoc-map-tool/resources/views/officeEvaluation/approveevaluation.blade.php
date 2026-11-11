<?php
    use App\Helpers\H;
    
?>
<x-modal>
    <x-slot name="id">approveevaluation</x-slot>
    <x-slot name="size">w60</x-slot>
        <x-slot name="title">Office Evaluation Approval</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    <form id="approveEvaluationApprovalForm" class="approveEvaluationApprovalForm">
        <input type="hidden" id="evaluation_id" name="evaluation_id" value="<?php echo $data['id']?>"/>
        
       
        <section class="supervisory_personnel mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Are you sure you want to approve this evaluation ?</h5>
            </header>
            
        </section>
       
       
        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <button class="btn btn-primary submit" onclick="approveEvaluationForm(event, this, 'save');">Yes</button>
                <button class="btn btn-primary" onclick="closeEvaluation(event, this, 'save');">No</button>
            </div>
        </div>
    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script type="text/javascript">
    function approveEvaluationForm(e, button, action) {
        e.preventDefault();
        button.innerHTML = 'Please wait...';
        button.disable = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        let data = $('#approveEvaluationApprovalForm').serializeArray();
        data.push({name: "_token", value: _token});
        
        $.ajax({
            url: "{{route('officeevaluation.saveapprovalform')}}",
            type: 'post',
            data: $.param(data),
            success: function(data) {
                button.disable = false;
                button.innerHTML = 'Yes';
                if (data.status) {
                    $('#approveevaluation').modal('hide');
                    $('#dataTable-leadership-office_evaluation').DataTable().ajax.reload();
                }
            }
        });
    }

    function closeEvaluation(e, button, action) {
        e.preventDefault();
        $('#approveevaluation').modal('hide');
        $('#dataTable-leadership-office_evaluation').DataTable().ajax.reload();
    }
</script>
