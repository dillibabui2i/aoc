<?php
    use App\Helpers\H;
    
?>
<x-modal>
    <x-slot name="id">completerenewableform</x-slot>
    <x-slot name="size">w60</x-slot>
        <x-slot name="title">Complete Renewable</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    <form id="completeRenewableForm" class="completeRenewableForm">
        <input type="hidden" id="renewable_id" name="renewable_id" value="<?php echo $renewable_id?>"/>
        <input type="hidden" id="bucket_type" name="bucket_type" value="<?php echo $bucket_type?>"/>
        
       
        <section class="supervisory_personnel mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Are you sure you want to complete this Renewable ?</h5>
            </header>
            
        </section>
       
       
        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <button class="btn btn-primary submit" onclick="saveCompleteRenewableForm(event, this, 'save');">Yes</button>
                <button class="btn btn-primary" onclick="closeCompleteRenewable(event);">No</button>
            </div>
        </div>
    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script type="text/javascript">
    function saveCompleteRenewableForm(e, button, action) {
        e.preventDefault();
        button.innerHTML = 'Please wait...';
        button.disable = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        let data = $('#completeRenewableForm').serializeArray();
        data.push({name: "_token", value: _token});
        
        $.ajax({
            url: "{{route('renewables.savecompleterenewableform')}}",
            type: 'post',
            data: $.param(data),
            success: function(data) {
                button.disable = false;
                button.innerHTML = 'Yes';
                if (data.status) {
                    $('#completerenewableform').modal('hide');
                    $('#dataTable-expiring-<?php echo $bucket_type?>').DataTable().ajax.reload();
                }
            }
        });
    }

    function closeCompleteRenewable(e) {
        e.preventDefault();
        $('#completerenewableform').modal('hide');
        $('#dataTable-expiring-<?php echo $bucket_type?>').DataTable().ajax.reload();
    }
</script>
