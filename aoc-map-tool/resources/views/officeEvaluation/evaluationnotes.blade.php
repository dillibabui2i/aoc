<?php
    use App\Helpers\H;

?>
<x-modal>
    <x-slot name="id">officeEvaluationnotes</x-slot>
    <x-slot name="size">w60</x-slot>
        <x-slot name="title">Office Evaluation Notes</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    <form id="officeEvaluationNotes" class="officeEvaluationNotes">
        <input type="hidden" id="evaluation_id" name="evaluation_id" value="<?php echo $data['id']?>"/>


        <section class="supervisory_personnel mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Supervisory Personnel Only</h5>
            </header>
            <table class="table table-bordered" aria-describedby="">
                <tbody>
                    <tr>
                        <td><textarea name="notes" id="notes" style="width:100%"></textarea></td>
                    </tr>
                </tbody>
            </table>
        </section>


        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <button class="btn btn-primary submit" onclick="submitOfficeEvaluationNotes(event, this, 'save');">Save</button>
            </div>
        </div>
    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script type="text/javascript">
    function submitOfficeEvaluationNotes(e, button, action) {
        e.preventDefault();
        button.innerHTML = 'Please wait...';
        button.disable = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        let data = $('#officeEvaluationNotes').serializeArray();
        data.push({name: "_token", value: _token});

        $.ajax({
            url: "{{route('officeevaluation.savenotesform')}}",
            type: 'post',
            data: $.param(data),
            success: function(data) {
                button.disable = false;
                button.innerHTML = 'Submit';
                if (data.status) {
                    $('#officeEvaluationnotes').modal('hide');
                    $('#dataTable-leadership-office_evaluation').DataTable().ajax.reload();
                }
            }
        });
    }
</script>
