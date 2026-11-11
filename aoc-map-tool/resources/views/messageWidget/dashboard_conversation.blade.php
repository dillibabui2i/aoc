<?php
use App\Helpers\H;
use App\Helpers\LD;

$recruiters = H::getHelper("ConstantHelper")->getRecruiters('id');
$lastResponse = [['value' =>'External','text' => 'External'],['value' =>'Internal','text' => 'Internal']];
$messageCenterConversationFilter = [
        [
            'label'=>'Me Mode',
            'name'=>'dconmemode',
            'type'=>'checkbox',
            'value'=>true
        ],
        [
        'label'=>'Recruiter',
        'name'=>'Recruiter',
        'type'=>'multiselect',
        'source'=>$recruiters
    ],
        [
        'label'=>'Last Response',
        'name'=>'last_response',
        'type'=>'select',
        'source'=>$lastResponse
    ]
];

 ?>
<div class="card">
    <div class="card-header" id="conversations">
        <h2 class="mb-0">
            <button class="col-md-11 pull-left btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                data-target="#conversations_childs" aria-expanded="false" aria-controls="collapseThree">
                Conversations<i class="fa fa-chevron-right"></i>
            </button>
        </h2>
    </div>
    <div id="conversations_childs" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
        <div class="card-body">
            <div class="card">
                <div class="card-header" id="message-center-conversations">
                    <h3 class="mb-0">
                        <button class="col-md-11 pull-left btn btn-link btn-block text-left collapsed" type="button"
                            data-toggle="collapse" data-target="#message_center_conversations" aria-expanded="false"
                            aria-controls="collapseOne">
                            Conversations  <span id="message_center_conversations-counter"></span><i class="fa fa-chevron-right"></i>
                        </button>
                    </h3>
                </div>
                <div id="message_center_conversations" class="collapse " aria-labelledby="dailyactivityone" data-parent="#conversations_childs">
                    <div class="filters-common">
                        @component('components.datatable-filters',['filters'=>$messageCenterConversationFilter,'formname'=>'messagecenterconversationsform'])
                        @endcomponent
                    </div>
                    <div class="card-body">
                        <?php echo H::getHelper("MessageWidget")->getDataTableHtml("message_center_conversations")?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>

    const messageCoversationBucketArray = ['message_center_conversations'];
    $(document).on('submit', '#messagecenterconversationsform', function(e) {
        e.preventDefault();
        var serializeData = $("#messagecenterconversationsform").serializeArray();
        for(var i=0; i < messageCoversationBucketArray.length;i++) {
            var tempObject = messageCoversationBucketArray[i].replace(/ /g,"_");
            if($("#reload-"+tempObject).val()==1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('dashboard.reloadcountmessagecenterlist') }}?type="+messageCoversationBucketArray[i],
                    type: "POST",
                    data: {
                        filters: serializeData,
                        _token: _token
                    },
                    success: function(response) {
                        var jsonObject = JSON.parse(response);
                        var type = jsonObject.type;
                        var idObject = type.replace(/ /g,"_");
                        $("#"+idObject+'-counter').html(jsonObject.counter);
                    },
                });
            } else {
                console.log("messagecenterconversationsform=tempObject="+tempObject);
                $("#dataTable-messsage_conversation-"+tempObject).DataTable().ajax.reload();
            }
        }
    });
    $("#messagecenterconversationsform").trigger("submit");
    </script>
