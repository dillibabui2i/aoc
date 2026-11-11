<?php
    use App\Helpers\H;
    use App\Helpers\LD;

    if(LD::checkFeatureFlag('careteam-tagging-to-notes') && ($data['entity_type']=='client')) {
        $users = H::getHelper("ConstantHelper")->getUsersWithCareTeam();
    } else {
        $users = H::getHelper("ConstantHelper")->getUsers();
    }
    $user = [];
    $cnt =0 ;
    foreach($users as $u) {
        $user[$cnt]["key"] =$u['name'];
        $user[$cnt]["value"] = $u['name'];
        $user[$cnt]["email"] = $u['email'];
        $user[$cnt]["id"] = $u['id'];
        $cnt++;
    }

?>
<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<script src="{{ mix('js/tribute.js') }}"></script>
<link href="{{ mix('css/tribute.css') }}" rel="stylesheet">

<x-modal>
    <x-slot name="id">notes-popup</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Create Notes</x-slot>

    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 notes-form">
            <form method="post" autocomplete="off" id="notes_form" class="scaffold-form" enctype="multipart/form-data">
                @csrf

                <input type="hidden" name="entity_id" value="<?php echo $data['entity_id']?>">
                <input type="hidden" name="entity_type" value="<?php echo $data['entity_type']?>">
                <input type="hidden" name="type" id="type" value="<?php echo $data['type']?>" />
                <input type="hidden" name="mention" id="mention" value="" />
                <input type="hidden" name="notes_text" id="notes_text" value="" />
                <input type="hidden" name="title" id="title" value="<?php echo isset($data['notesTitle'])?$data['notesTitle']:''?>" />
                <div class="row pt-1">
                    <div class="col-md-12">
                        <label for="notes">Notes</label>
                        <div id="notes" contenteditable="true">

                        </div>
                    </div>
                </div>
                @if(LD::checkFeatureFlag('option-to-send-sms-from-note-client-profile'))
                @if(!empty($data['page']) && $data['page'] == 'intake_client_profile')
                @include('intake.sendIntakeSms',['from_page' => 'client'])
                @endif
                @endif
                <div id="notes_save" class="row pt-2">
					<div class="col-md-12 mt-3 text-right">
						<button type="button" onclick="submitNotes()" class="btn btn-primary">Save</button>
					</div>
				</div>
            </form>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<style>
    .w35 {
        width:35%;
    }
    .w25 {
        width:25%;
    }
    .notes.form-control {
        height:130px;
    }

    .w35 {
        width:35%;
    }
    .w25 {
        width:25%;
    }
    .notes.form-control {
        height:130px;
    }
    div.editable {
        width: 300px;
        height: 200px;
        border: 1px solid #ccc;
        padding: 5px;
    }

    strong {
    font-weight: bold;
    }

    #notes {
        background-clip: padding-box;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        color: #495057;
        display: block;
        font-size: .9rem;
        font-weight: 400;
        line-height: 1.6;
        padding: 0.375rem 0.75rem;
        transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        width: 100%;
        height: 150px;
    }
    #notes:focus {
        background-color: #fff;
        border-color: #a1cbef;
        box-shadow: 0 0 0 0.2rem rgb(52 144 220 / 25%);
        color: #495057;
        outline: 0;
    }
</style>

<script>
    $('#due_date').datepicker();
</script>
@if(!empty($data['page']) && $data['page'] == 'intake_client_profile')
<script>
    $('.smscareteam').removeClass('hide');
</script>
@endif
<script>
      var jsonFields = JSON.parse('<?php echo addslashes(json_encode($user))?>');


      var tribute = new Tribute({
        values: jsonFields,
        selectTemplate: function(item) {
          if (typeof item === "undefined") return null;
         // console.log(this.range.isContentEditable(this.current.element));
        //  if (this.range.isContentEditable(this.current.element)) {
            return (
              '<a class="tag-container" data-id="'+item.original.id+'" href="#" target="_blank" title="' +
              item.original.email +
              '">@' +
              item.original.value +
              "</a>"
            );
         // }

         // return "@" + item.original.value;
        }
      });

      tribute.attach($('#notes_form #notes'));

      function submitNotes(){
        @if(LD::checkFeatureFlag('option-to-send-sms-from-note-client-profile'))
        @if(!empty($data['page']) && $data['page'] == 'intake_client_profile')
		var $dropdown = $("#client_careTeamDropdown");
		if ($dropdown.length){
        if(!$("#client_careTeamDropdown").valid()){
            return false;
        }}
        @endif
        @endif
        var html = $('#notes_form #notes').html();
       // console.log(html);
        var $obj = $("<div>"+html+"</div>").find("a");
       // console.log($obj);
        var idArray = [];
        var cntr = 0;
        $obj.each(function(i,item){
            idArray[cntr] = $(item).attr("data-id");
            cntr++;
        });
        @if (LD::checkFeatureFlag('enhance-notes-popup-for-triage'))
            if($("input[name='entity_type']").val() == 'triage'){
                var notes = html;
            }else{
                var notes = $("<div>"+html+"</div>").text();
            }
        @else
            var notes = $("<div>"+html+"</div>").text();
        @endif

        var ids = idArray.join(",");
        $("#mention").val(ids);
        $("#notes_text").val(notes);

        $.ajax({
            url: "{{route('savenotes')}}",
            method: "POST",
            data: $("#notes_form").serialize(),

            success: function(data) {
                $('#notes-popup').modal('hide');
            }
        });
      }
    </script>
