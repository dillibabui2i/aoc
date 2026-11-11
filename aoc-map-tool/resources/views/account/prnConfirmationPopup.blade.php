<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    use App\Helpers\ConstantHelper;
    
?>

<x-modal>
    <x-slot name="id">prnconfirmationdialog</x-slot>
    <x-slot name="size">w20</x-slot>
    <x-slot name="title">Confirmation Dialog</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 tracker-details client-match-template text-center">
                <input type="hidden" name="tracker_id" id="tracker_id" value="<?php echo $id?>" />
                Are you sure you want to move this tracker to the show all page?
            </div>

            <div class="col-md-12 pt-3 text-center">
                <button class="btn btn-primary" type="button" onclick="performPRNConfirmAction()">Yes</button>
                <button class="btn btn-primary" type="button" onclick="closeThisPRNPopup()">No</button>
            </div>
            
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>
<script>
    function performPRNConfirmAction(){
        let tracker_id = $("#tracker_id").val();
        let _token = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
			url: "{{route('prn.excludebucket')}}",
			type: "GET",
			data: {
				_token: _token,
				tracker_id: tracker_id
			},
			success: function(response) {
                $('#prnconfirmationdialog').modal('hide');
				$('.dataTable').DataTable().ajax.reload(null, false);
				
			},
		});


    }

    function closeThisPRNPopup(){
        $('#prnconfirmationdialog').modal('hide');
    }
</script>