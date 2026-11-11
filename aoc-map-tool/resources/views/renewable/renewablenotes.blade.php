<?php
    use App\Helpers\H;

?>
<x-modal>
    <x-slot name="id">renewablenotesform</x-slot>
    <x-slot name="size">w60</x-slot>
        <x-slot name="title">Renewable Notes</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    <form id="renewableNotes" class="renewableNotes">
        <input type="hidden" id="renewable_id" name="renewable_id" value="<?php echo $renewable_id?>"/>
        <input type="hidden" id="bucket_type" name="bucket_type" value="<?php echo $bucket_type?>"/>



        <section class="supervisory_personnel mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Write Notes</h5>
            </header>
            <table class="table table-bordered" aria-describedby="" aria-hidden="true">
                <tbody>
                    <tr>
                        <td><textarea class="form-control" name="notes" id="notes" style="width:100%"></textarea></td>
                    </tr>
                </tbody>
            </table>
        </section>


        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <button class="btn btn-primary submit" onclick="submitRenewableNotes(event, this, 'save');">Save</button>
            </div>
        </div>
    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script type="text/javascript">
    function submitRenewableNotes(e, button, action) {
        e.preventDefault();
        button.innerHTML = 'Please wait...';
        button.disable = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        let data = $('#renewableNotes').serializeArray();
        data.push({name: "_token", value: _token});

        $.ajax({
            url: "{{route('renewables.saverenewablenotesform')}}",
            type: 'post',
            data: $.param(data),
            success: function(data) {
                button.disable = false;
                button.innerHTML = 'Submit';
                if (data.status) {
                    $('#renewablenotesform').modal('hide');
                    $('#dataTable-expiring-<?php echo $bucket_type?>').DataTable().ajax.reload();
                }
            }
        });
    }
</script>
