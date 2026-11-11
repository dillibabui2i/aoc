<?php
    use App\Helpers\H;
    
?>
<x-modal>
    <x-slot name="id">nursyskantimepopup</x-slot>
    <x-slot name="size">w60</x-slot>
        <x-slot name="title">Nursys License Mismatch</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    <form id="approveNursysRenewableForm" class="approveNursysRenewableForm">
        <input type="hidden" id="renewable_id" name="renewable_id" value="<?php echo $data['renewable_id']?>"/>
        
       
        <section class="supervisory_personnel mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Are you sure you want to update information in KanTime ?</h5>
            </header>
            
        </section>
       
       
        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <button class="btn btn-primary submit" onclick="approveNursysForm(event, this, 'save');">Yes</button>
                <button class="btn btn-primary" onclick="closeNursys(event, this, 'save');">No</button>
            </div>
        </div>
    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script type="text/javascript">
    function approveNursysForm(e, button, action) {
        e.preventDefault();
        button.innerHTML = 'Please wait...';
        button.disable = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        let data = $('#approveNursysRenewableForm').serializeArray();
        data.push({name: "_token", value: _token});
        
        $.ajax({
            url: "{{route('nursys.savenursysexceptionform')}}",
            type: 'post',
            data: $.param(data),
            success: function(data) {
                button.disable = false;
                button.innerHTML = 'Yes';
                if (data.status) {
                    $('#nursyskantimepopup').modal('hide');
                    $('#dataTable-nursys-nursyslicensemismatch').DataTable().ajax.reload();
                }
            }
        });
    }

    function closeNursys(e, button, action) {
        e.preventDefault();
        $('#nursyskantimepopup').modal('hide');
        $('#dataTable-nursys-nursyslicensemismatch').DataTable().ajax.reload();
    }
</script>
