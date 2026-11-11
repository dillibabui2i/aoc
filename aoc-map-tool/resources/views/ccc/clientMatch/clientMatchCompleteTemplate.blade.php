<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    use App\Helpers\ConstantHelper;
    
?>

<x-modal>
    <x-slot name="id">cccClientMatchCompleteTemplate</x-slot>
    <x-slot name="size">w20</x-slot>
    <x-slot name="title">Confirmation Dialog</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 tracker-details client-match-template text-center">
                <input type="hidden" name="update_ids" id="update_ids" value="<?php echo $ids?>" />
                Has the schedule been built to the authorization in KanTime?
            </div>

            <div class="col-md-12 pt-3 text-center">
                <button class="btn btn-primary" type="button" onclick="performCompleteAction()">Yes</button>
                <button class="btn btn-primary" type="button" onclick="closeThisPopup()">No</button>
            </div>
            
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>
<script>
    function performCompleteAction(){
        let ids = $("#update_ids").val();
        let _token = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
			url: "{{route('ccc.performclientmatchcomplete')}}",
			type: "GET",
			data: {
				_token: _token,
				ids: ids
			},
			success: function(response) {
                $('#cccClientMatchCompleteTemplate').modal('hide');
                $('#dataTable-clientcare-exception-ccc_client_match_wrapper div.updateall').remove();
				$('.dataTable').DataTable().ajax.reload(null, false);
				
			},
		});


    }

    function closeThisPopup(){
        $('#cccClientMatchCompleteTemplate').modal('hide');
    }
</script>