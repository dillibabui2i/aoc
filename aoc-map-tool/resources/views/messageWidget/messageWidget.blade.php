<link href="{{ mix('css/messageWidget.css') }}" rel="stylesheet">
<input id="chatWidgetIcon" class="hide" type="checkbox">
    <label class="chat-btn" for="chatWidgetIcon">
        <i class="fa fa-commenting-o comment">
        </i>
        <i class="fa fa-close close">
        </i>
    </label>
    <div class="wrapper chat-widget">
        <div class="header m-0 d-flex">
            <h6 class="m-0 col-md-10 p-0">
                Message Center
            </h6>
            <a class="expand-widget expand"><i class="fa fa-expand"></i><i class="fa fa-compress"></i></a>
            <a class="full-page-icon" href="{{route('messageWidget.fullPageView')}}"><i class="fa fa-arrows-alt" aria-hidden="true"></i></a>
        </div>
        <div class="user-list-section">
            <div class="search-area search-area w-100 pull-left pt-2 pb-2">
                <div class="col-md-12">
                    <div class="form-group w-100 m-0">
                        <div class="form-group has-search w-100 m-0">
                            <span class="fa fa-search form-control-feedback"></span>
                            <input type="text" name="widgetUserSearch" onkeyup="findConversation(this);" id="widgetUserSearch" class="form-control" placeholder="Find a Conversation">
                        </div>
                    </div>
                </div>
            </div>
            <div class="user-list pt-2">
                <ul>
                    Fetching the Conversation...
                </ul>
            </div>
        </div>

        <div class="user-chat-section">
            <div class="user-chat-header w-100 pull-left border-bottom pt-2 pb-2">
                <div class="col-md-12 d-flex align-items-center">
                    <a href="#" id="backToUserList" class="pr-2"><i class="fa fa-angle-left" aria-hidden="true"></i></a>
                    <p class="pr-2 chatUserName font-weight-bold" id="chatUserName">...</p>
                    <p class="text-muted chatUserPhone" id="chatUserPhone">...</p>
                </div>
            </div>
            <div class="user-messages">
                <ul>
                    Fetching the Messages...
                </ul>
            </div>
            <div class="user-chat-footer">
                <div class="col-md-12 text-muted d-flex justify-content-start align-items-center p-1">
                    <textarea class="form-control m-0" id="userMessage" name="userMessage" placeholder="Enter a message...&#10;To send use button or Ctrl + Enter"></textarea>
                    <a class="pl-3" href="javascript:void(0);" id="fileAttachmentBtn">
                        <i class="fa fa-paperclip"></i>
                    </a>
                    <a class="pl-3" href="javascript:void(0);" id="sendMessageBtn">
                        <i class="fa fa-paper-plane"></i>
                        <i class="fa fa-spinner hide"></i>
                    </a>

                </div>
            </div>
        </div>
    </div>
</input>


<!-- File attachment box -->
<div class="file-attachment-box" id="fileAttachmentBox">
    <div class="card">
        <div class="card-header">
            Select File to attach
            <a href="javascript:void(0);" class="pull-right" id="fileAttachmentCloseBtn">
                <i class="fa fa-close"></i>
            </a>
        </div>
        <ul class="list-group list-group-flush">
            Loading file list...
        </ul>
        <div class="card-body text-center">
            <button id="attachmentDoneBtn" disabled="true" class="btn btn-primary">Done</button>
        </div>
    </div>
</div>
<script>
      /**
     * Common Element action Script for the widget
     */

     $(document).on('click','.user-list-section li',async function(e){
        if($(this).attr('data-conversation-id') != ''){
            await loadMessagesByConversation($(this).attr('data-conversation-id'));
        }
        $('.user-list-section').hide();
        $('.user-chat-section').show("slide", { direction: "right" }, 300);
    });
    $(document).on('click','#backToUserList',function(e){
        $('#chatUserName').html('...');
        $('#chatUserPhone').html('...');
        $('.user-messages ul').html('Fetching the Messages...');
        $('.user-chat-section').hide();
        $('.user-list-section').show("slide", { direction: "left" }, 300);
        if(widget.currentConversation != ''){
            $('.user-list ul').find('li[data-conversation-id="'+widget.currentConversation.sid+'"] .unread-message-count').remove();
        }
    });
    $(document).on('change','#chatWidgetIcon',function(){
        if($(this).is(":checked")){
            $('.chat-widget').show();
            //loadConversationToWidget();
        }else{
            $('.chat-widget').hide();
        }
    });
    $(document).on('click','#sendMessageBtn',async function(e){
        widget.disableButtons();
        const client = await widget.getClient();
        client.on('initialized', async() => {
            if(widget.currentConversation != ''){
                const text = $("#userMessage").val();
                if(text != ''){
                    await widget.sendMessage(widget.currentConversation,text);
                    widget.currentConversation.setAllMessagesRead();
                    widget.enableButtons();
                }else{
                    widget.enableButtons();
                }
                $("#userMessage").val('');
            }
        });
    });

    /**
     * When user press the Ctrl + Enter on the message textarea
     */
    $(document).on('keydown','#userMessage',async function(e){
        if (e.ctrlKey && e.keyCode == 13) {
            $('#sendMessageBtn').click();
        }
    });

    /**
     * To load the Conversation from the twilio into the widget
     */
    async function loadConversationToWidget(){
        try{
            const client = await widget.getClient();
            client.on('initialized', async() => {
                if(widget.allConversations.length == 0){
                    widget.allConversations = await widget.getAllSubscribedConversations(client);
                }

                // Sorting the Conversation base on the recent messages
                widget.allConversations.sort((a, b) => {
                    const aTimestamp = a.lastMessage ? Date.parse(a.lastMessage.dateCreated) : 0;
                    const bTimestamp = b.lastMessage ? Date.parse(b.lastMessage.dateCreated) : 0;
                    return bTimestamp - aTimestamp;
                });

                let listOfConversationStr = '';
                widget.allConversations.forEach(async function(conversation) {
                    // console.log(conversation.lastMessage.dateCreated,'dateCreated');
                    const profileImage = conversation.friendlyName.split(' ').map(word => word.charAt(0)).join('');
                    listOfConversationStr += '<li data-conversation-id="'+conversation.sid+'"><div class="profile-letter-image">'+profileImage+'</div><p>'+conversation.friendlyName+'</p></li>';
                    console.log('messageAdded...');
                    conversation.on("messageAdded",widget.onMessageAdd);
                    widget.setUnreadMessagesCount(conversation);
                });
                $('.user-list ul').html(listOfConversationStr);

                //Opening the conversation if requested
                console.log(widget.openRequestedConversation,'widget.openRequestedConversation');
                if($('.user-list ul').find('li[data-conversation-id="'+widget.openRequestedConversation.sid+'"]').length > 0){
                    //$('.user-list ul').find('li[data-conversation-id="'+widget.openRequestedConversation.sid+'"]').addClass('new-conversation');
                    $('.user-list ul').find('li[data-conversation-id="'+widget.openRequestedConversation.sid+'"]').click();
                    widget.openRequestedConversation = '';
                }else{
                    const profileImage = widget.openRequestedConversation.friendlyName.split(' ').map(word => word.charAt(0)).join('');
                    $('.user-list ul').append('<li class="new-conversation" data-conversation-id="'+widget.openRequestedConversation.sid+'"><div class="profile-letter-image">'+profileImage+'</div><p>'+widget.openRequestedConversation.friendlyName+'</p></li>');
                    $('.user-list ul').find('li[data-conversation-id="'+widget.openRequestedConversation.sid+'"]').click();
                    widget.openRequestedConversation.on("messageAdded",widget.onMessageAdd);
                }
            });
        }catch(e){
            console.log('loadConversationToWidget:',e);
        }
    }

    /**
     * To load the Messages of the Conversation from the twilio into the widget
     */
    async function loadMessagesByConversation(conversationId){
        const client = await widget.getClient();
        client.on('initialized', async() => {
            const conversation = await widget.getConversationsById(conversationId,client);
            const messageCount = await conversation.getUnreadMessagesCount();
            conversation.setAllMessagesRead();
            widget.allMessageCount -= messageCount;
            widget.setAllMessageCount();
            widget.setConversationToReadToDB(conversationId);
            widget.currentConversation = conversation;
            $('#chatUserName').html(conversation.friendlyName);
            if(conversation.uniqueName.indexOf('#') !== -1){
                $('#chatUserPhone').html(widget.getFormattedNumber(conversation.uniqueName.split('#')[1]));
                if(typeof conversation.uniqueName.split('#')[2] !== 'undefined' && conversation.uniqueName.split('#')[2] != ''){
                    $('#fileAttachmentBtn').attr('data-user-type',conversation.uniqueName.split('#')[2]);
                }
            }
            const messages = await conversation.getMessages();
            let messageStr = '';
            var lastDate = '';
            var lastUser = '';
            messages.items.forEach(function(message) {
                messageClass = (message.author === widget.author.replace('+','')) ? 'right' : 'left';
                let msg = widget.parseMessageToCheckUrlAndFiles(message.body);
                const dateCreated = widget.getFormattedDate(message.dateCreated);
                if(dateCreated !== lastDate){
                    messageStr += '<span class="msg-date">'+dateCreated+'</span>';
                    lastDate = dateCreated;
                }
                if(message.author !== widget.author.replace('+','') && lastUser !== message.author && conversation.uniqueName.indexOf(message.author+'#') === -1){
                    messageStr += '<span class="msg-user">'+widget.twilioUsers['+'+message.author]+'</span>';
                }else if(conversation.uniqueName.indexOf(message.author+'#') !== -1 && lastUser !== message.author){
                    messageStr += '<span class="msg-user">'+conversation.friendlyName+'</span>';
                }
                lastUser = message.author;

                messageStr += '<li data-message-id="'+message.sid+'" class="'+messageClass+'"><p>'+msg+'<span class="msg-time">'+widget.getFormattedTime(message.dateCreated)+'</span></p></li>';
            });
            $('.user-messages ul').html(messageStr);
            $('.user-chat-section ul').animate({scrollTop: $('.user-chat-section ul').prop('scrollHeight')}, "slow");
        });
    }

    /**
     * Filter the conversation list by type
     */
    function findConversation(element) {
        var value = $(element).val().toLowerCase();
        $(".user-list li").each(function() {
            if ($(this).find('p').html().toLowerCase().indexOf(value) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    /**
    * Expanding and Compressing the widget window
    */
    $(document).on('click','.expand-widget',async function(e){
        if($(this).hasClass('expand')){
            $(this).removeClass('expand');
            $(this).addClass('compress');
            $(this).find('.fa-expand').hide();
            $(this).find('.fa-compress').show();
            $('.chat-widget .user-list ul').css({"height":'500px'});
            $('.chat-widget .user-messages ul').css({"height":'500px'});
        }else{
            $(this).removeClass('compress');
            $(this).addClass('expand');
            $(this).find('.fa-compress').hide();
            $(this).find('.fa-expand').show();
            $('.chat-widget .user-list ul').css({"height":''});
            $('.chat-widget .user-messages ul').css({"height":''});
        }

    });

    /**
     * Candidate details page chat icon action to create the conversation
     */
    $(document).on('click','#startConversationWithCandidateBtn,#startConversationWithClinicianBtn',async function(e){
        try{
            $(this).find('.fa').removeClass('fa-comment-o').addClass('fa-spinner');
            const client = await widget.getClient();
            const name = $(this).attr('data-candidate-name');
            const id = $(this).attr('data-candidate-id');
            const typeOfUser = $(this).attr('id') == 'startConversationWithCandidateBtn' ? 'candidate':'clinician';
            const phone = widget.formatPhoneForMessage($(this).attr('data-candidate-phone'));
            let isExist = 0;
            //Logs
            ESSLog.log('Clicked on chat icon from Jobvite profile for '+typeOfUser+"("+name+")-"+id,"clinician_candidate_widget.log",function(){});
            client.on('initialized', async() => {
                if(widget.allConversations.length == 0){
                    widget.allConversations = await client.getSubscribedConversations();
                    widget.allConversations = widget.allConversations.items;
                }
                widget.allConversations.forEach(function(conversation) {
                    const uniqueNameParts = conversation.uniqueName.split('#');
                    if(uniqueNameParts.includes(id)){
                        ESSLog.log('Conversation is already created so opening the chat and Conversation ID:'+conversation.sid,"clinician_candidate_widget.log",function(){});
                        widget.closeModalPopup();
                        widget.openWidgetWindow();
                        widget.openConversation(conversation);
                        isExist = 1;
                        return;
                    }
                });
                if(!isExist){
                    if(name != '' && id != '' && phone != ''){
                        const conversation = await client.createConversation({
                            attributes: {},
                            friendlyName: name,
                            uniqueName: id+'#'+phone+'#'+typeOfUser+'#'+widget.fromNumber
                        });
                        conversation.join();
                        console.log(conversation,'conversation');
                        ESSLog.log('Conversation created and Conversation ID:'+conversation.sid,"clinician_candidate_widget.log",function(){});
                        await widget.addParticipant(conversation,widget.fromNumber,phone);
                        addConversationToDB({
                            _token:typeof _token !== 'undefined' ? _token : $('meta[name="csrf-token"]').attr('content'),
                            sid:conversation.sid,
                            receiver_mobile:phone,
                            unique_name:conversation.uniqueName,
                            friendly_name:conversation.friendlyName,
                            aoc_provider_id:typeOfUser != 'candidate' ? id : 0,
                            aoc_candidate_id:typeOfUser == 'candidate' ? id : 0,
                        });
                        widget.closeModalPopup();
                        widget.openConversation(conversation);
                        widget.openWidgetWindow();
                    }else{
                        ESSLog.log('Conversation is not created due to required info:Name, Id and phone'+name+','+id+','+phone,"clinician_candidate_widget.log",function(){});
                        console.log("Missing information to start conversation");
                    }
                }
            });
        }catch(e){
            console.log("Starting Candidate Conversation Err:",e);
            ESSLog.log("Starting Candidate Conversation Err:"+e,"clinician_candidate_widget.log",function(){});
        }
    });
    async function addConversationToDB(data){
        await widget.ajaxCall("{{route('messageWidget.addConversationToDB')}}",'POST',data);
    }
    /**
     * File attachment
     */
    $(document).on('click','#fileAttachmentBtn',function(){
        $('#fileAttachmentBox').show();
        $('#attachmentDoneBtn').attr('disabled','true');
        widget.loadFileSelectionList('#fileAttachmentBox ul',$('#fileAttachmentBtn').attr('data-user-type'));
        widget.currentSelectedFiles = [];
    });
    $(document).on('click','#attachmentDoneBtn',function(){
        widget.currentSelectedFiles = [];
        $('#fileAttachmentBox input:checked').each(function() {
            widget.currentSelectedFiles.push($(this).val());
        });
        $('#fileAttachmentBox').hide();
        $('#fileAttachmentBtn').find('.fileAttachmentCount').remove();
        if(widget.currentSelectedFiles.length > 0){
            $('#fileAttachmentBtn').append('<span class="fileAttachmentCount">'+widget.currentSelectedFiles.length+'</span>');
        }
    });
    $(document).on('click','#fileAttachmentCloseBtn',function(){
        $('#fileAttachmentBox').hide();
        $('#fileAttachmentBtn').find('.fileAttachmentCount').remove();
        if(widget.currentSelectedFiles.length > 0){
            $('#fileAttachmentBtn').append('<span class="fileAttachmentCount">'+widget.currentSelectedFiles.length+'</span>');
        }
    });
    $(document).on('click','#fileAttachmentBox input[type=checkbox]',function(){
        if($('#fileAttachmentBox input:checked').length > 0){
            $('#attachmentDoneBtn').removeAttr('disabled');
        }else{
            $('#attachmentDoneBtn').attr('disabled','true');
        }
    });

    $(document).ready(function(e){
        loadConversationToWidget();
    });
</script>
