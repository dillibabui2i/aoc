@extends('layouts.myaccount')
@section('title', 'Message Center')
@section('content')

<link href="{{ mix('css/messageWidget.css') }}" rel="stylesheet">
<style>
    .left-side{
        display: none !important;
    }
    .chat-btn{
        display: none !important;
    }
    .full-page-widget .full-page-inner{
        display: flex;
        min-height: 85vh;
    }
    .full-page-widget .full-page-inner{
        display: flex;
        min-height: 85vh;
    }
    .user-chat-section-inner{
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .user-chat-section-inner .user-messages-full-page{
        display: flex;
        overflow: auto;
        height: 85vh;
    }
    .user-chat-section-inner .user-messages-full-page ul{
        height: auto !important;
    }
    .full-page-widget .user-list-full-page ul{
        height: auto !important;
    }
    .user-list-full-page li.active{
        background-color: #215eac;
        color: #fff;
    }
    .user-list-full-page li.active{
        background-color: #215eac;
        color: #fff;
    }
    .full-page-widget .user-list-full-page li.active .profile-letter-image{
        background-color: #fff;
        color: #215eac;
    }
    .user-details-section-full-page{
        display: flex;
        flex-direction: column;
        background: #f2f2f2;
        padding: 10px;
    }
    .user-details-section-full-page .profile-letter-image{
        align-self: center;
        height: 80px;
        line-height: 80px;
        width: 80px;
    }
    .full-page-widget .user-messages-full-page ul{
        background-color: #f2f2f2;
        padding-top: 10px;
    }
    .full-page-widget .user-list-section-full-page{
        background-color: #f2f2f2;
        overflow-y: auto;
        height: 90vh;
    }
    .full-page-widget .user-messages-full-page .right p{
        max-width: initial;
        min-width: initial;
    }
    .full-page-widget .user-messages-full-page .left p{
        max-width: initial;
        min-width: initial;
    }
    .nav-link{
        padding: 5px;
    }
    .nav-pills .nav-link{
        border-radius: 0px;
    }
    .filter-cnt{
        border-bottom: 1px solid #ddd;
    }
</style>

<div class="wrapper full-page-widget">
    <div class="col-md-12 p-0 full-page-inner">
        <div class="user-list-section-full-page col-md-2">
            <div class="search-area search-area w-100 pull-left pt-2 pb-2">
                <div class="col-md-12 p-0">
                    <div class="form-group w-100 m-0">
                        <div class="form-group has-search w-100 m-0">
                            <span class="fa fa-search form-control-feedback"></span>
                            <input type="text" name="widgetUserSearchFullPage" onkeyup="findConversationFullPage(this);" id="widgetUserSearchFullPage" class="form-control" placeholder="Find a Conversation">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 p-0 w-100 pull-left pb-2 filter-cnt">
                <ul class="nav nav-pills">
                    <li class="nav-item w-50">
                        <a id="allFilter" class="nav-link active" data-toggle="pill" href="javascript:void(0)" role="tab" aria-controls="pills-all" aria-selected="true">All</a>
                    </li>
                    <li id="unreadFilter" class="nav-item w-50">
                        <a class="nav-link" data-toggle="pill" href="javascript:void(0)" role="tab" aria-controls="pills-unread" aria-selected="false">Unread</a>
                    </li>
                </ul>
            </div>
            <div class="user-list-full-page pt-2">
                <ul>
                    Fetching the Conversation...
                </ul>
            </div>
        </div>

        <div class="user-chat-section-full-page col-md-8">
            <div class="user-chat-section-inner">
                <div class="user-messages-full-page">
                    <ul>
                        Fetching the Messages...
                    </ul>
                </div>
                <div class="user-chat-footer-full-page">
                    <div class="col-md-12 text-muted d-flex justify-content-start align-items-center p-1">
                        <textarea class="form-control m-0" id="userMessageFullPage" name="userMessage" placeholder="Enter a message...&#10;To send use button or Ctrl + Enter"></textarea>
                        <a class="pl-3" href="javascript:void(0);" id="fileAttachmentBtnFullPage">
                            <i class="fa fa-paperclip"></i>
                        </a>
                        <a class="pl-3" href="javascript:void(0);" id="sendMessageBtnFullPage">
                            <i class="fa fa-paper-plane"></i>
                            <i class="fa fa-spinner hide"></i>
                        </a>

                    </div>
                </div>
            </div>
        </div>
        <div class="user-details-section-full-page col-md-2 hide">
            <div class="profile-letter-image">...</div>
            <h3 id="chatUserNameFullPage">...</h3>
            <a href="" id="chatUserPhoneFullPage">
                ...
            </a>
            <button id="makeArchive" class="btn btn-primary mt-5" data-conversation-id="">Archive</button>
        </div>
    </div>
</div>

<!-- File attachment box -->
<div class="file-attachment-box-full-page" id="fileAttachmentBoxFullPage">
    <div class="card">
        <div class="card-header">
            Select File to attach
            <a href="javascript:void(0);" class="pull-right" id="fileAttachmentCloseBtnFullPage">
                <i class="fa fa-close"></i>
            </a>
        </div>
        <ul class="list-group list-group-flush">
            Loading file list...
        </ul>
        <div class="card-body text-center">
            <button id="attachmentDoneBtnFullPage" disabled="true" class="btn btn-primary">Done</button>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        SidebarCollapse();
        loadConversationToWidgetFullPage();
    });

    /**
     * To load the Conversation from the twilio into the widget
     */
    async function loadConversationToWidgetFullPage(){
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
                const profileImage = conversation.friendlyName.split(' ').map(word => word.charAt(0)).join('');
                listOfConversationStr += '<li data-conversation-id="'+conversation.sid+'"><div class="profile-letter-image">'+profileImage+'</div><p>'+conversation.friendlyName+'</p></li>';
                conversation.on("messageAdded",widget.onMessageAdd);
                widget.setUnreadMessagesCount(conversation);
            });
            $('.user-list-full-page ul').html(listOfConversationStr);

            //Opening the conversation if requested
            console.log(widget.openRequestedConversation,'widget.openRequestedConversation');
            if($('.user-list-full-page ul').find('li[data-conversation-id="'+widget.openRequestedConversation.sid+'"]').length > 0){
                //$('.user-list ul').find('li[data-conversation-id="'+widget.openRequestedConversation.sid+'"]').addClass('new-conversation');
                $('.user-list-full-page ul').find('li[data-conversation-id="'+widget.openRequestedConversation.sid+'"]').click();
                widget.openRequestedConversation = '';
            }else{
                const profileImage = widget.openRequestedConversation.friendlyName.split(' ').map(word => word.charAt(0)).join('');
                $('.user-list-full-page ul').append('<li class="new-conversation" data-conversation-id="'+widget.openRequestedConversation.sid+'"><div class="profile-letter-image">'+profileImage+'</div><p>'+widget.openRequestedConversation.friendlyName+'</p></li>');
                $('.user-list-full-page ul').find('li[data-conversation-id="'+widget.openRequestedConversation.sid+'"]').click();
                widget.openRequestedConversation.on("messageAdded",widget.onMessageAdd);
            }
        });
    }

    /**
     * To load the Messages of the Conversation from the twilio into the Full Page widget
     */
    async function loadMessagesByConversationFullPage(conversationId){
        const client = await widget.getClient();
        client.on('initialized', async() => {
            const conversation = await widget.getConversationsById(conversationId,client);
            const messageCount = await conversation.getUnreadMessagesCount();
            conversation.setAllMessagesRead();
            widget.allMessageCount -= messageCount;
            console.log(widget.allMessageCount,'widget.allMessageCount');
            widget.setAllMessageCount();
            widget.setConversationToReadToDB(conversationId);
            widget.currentConversation = conversation;
            $('#chatUserNameFullPage').html(conversation.friendlyName);
            $('#makeArchive').attr('data-conversation-id',conversation.sid);
            const profileImage = conversation.friendlyName.split(' ').map(word => word.charAt(0)).join('');
            $('.user-details-section-full-page .profile-letter-image').html(profileImage);
            if(conversation.uniqueName.indexOf('#') !== -1){
                $('#chatUserPhoneFullPage').html(widget.getFormattedNumber(conversation.uniqueName.split('#')[1]));
                if(typeof conversation.uniqueName.split('#')[2] !== 'undefined' && conversation.uniqueName.split('#')[2] != ''){
                    $('#fileAttachmentBtnFullPage').attr('data-user-type',conversation.uniqueName.split('#')[2]);
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
                    console.log(dateCreated,lastDate);
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
            $('.user-list-full-page ul').find('li[data-conversation-id="'+conversation.sid+'"]').find('.unread-message-count').remove();
            $('.user-messages-full-page ul').html(messageStr);
            $('.user-details-section-full-page').removeClass('hide');
            $('.user-chat-section-full-page ul').animate({scrollTop: $('.user-chat-section ul').prop('scrollHeight')}, "slow");
        });
    }

     /**
     * Common Element action Script for the widget For Full Page
     */
    $(document).on('click','#unreadFilter',async function(e){
        $('.user-list-full-page ul li').hide();
        $('.user-list-full-page ul').find('.unread-message-count').parent().show();
    });
    $(document).on('click','#allFilter',async function(e){
        $('.user-list-full-page ul li').show();
    });
    $(document).on('click','.user-list-full-page li',async function(e){
        $('.user-list-full-page li').removeClass('active');
        $(this).addClass('active');
        if($(this).attr('data-conversation-id') != ''){
            await loadMessagesByConversationFullPage($(this).attr('data-conversation-id'));
        }
        $('.user-chat-section-full-page').show();
    });
    $(document).on('click','#makeArchive',async function(e){
        const conversationId = $(this).attr('data-conversation-id');
        if (conversationId != '' && confirm("Are you sure you want archive the conversation?") == true) {
            const data = await widget.ajaxCall("{{route('messageWidget.setConversationToArchive')}}",'GET',{conversationId:conversationId});
            if(data.status === true){
                $('.user-list-section-full-page li[data-conversation-id="'+conversationId+'"]').remove();
                $('.user-list-section-full-page li').eq(0).click();
            }
        }
    });

    $(document).on('click','#sendMessageBtnFullPage',async function(e){
        widget.disableButtonsFullPage();
        const client = await widget.getClient();
        client.on('initialized', async() => {
            if(widget.currentConversation != ''){
                const text = $("#userMessageFullPage").val();
                if(text != ''){
                    await widget.sendMessage(widget.currentConversation,text);
                    widget.currentConversation.setAllMessagesRead();
                    widget.enableButtonsFullPage();
                }else{
                    widget.enableButtonsFullPage();
                }
                $("#userMessageFullPage").val('');
            }
        });
    });

    /**
     * When user press the Ctrl + Enter on the message textarea
     */
    $(document).on('keydown','#userMessageFullPage',async function(e){
        if (e.ctrlKey && e.keyCode == 13) {
            $('#sendMessageBtnFullPage').click();
        }
    });
    /**
     * Filter the conversation list by type
     */
    function findConversationFullPage(element) {
        var value = $(element).val().toLowerCase();
        $(".user-list-full-page li").each(function() {
            if ($(this).find('p').html().toLowerCase().indexOf(value) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    /**
     * File attachment
     */
    $(document).on('click','#fileAttachmentBtnFullPage',function(){
        $('#fileAttachmentBoxFullPage').show();
        $('#attachmentDoneBtnFullPage').attr('disabled','true');
        widget.loadFileSelectionList('#fileAttachmentBoxFullPage ul',$('#fileAttachmentBtnFullPage').attr('data-user-type'));
        widget.currentSelectedFiles = [];
    });
    $(document).on('click','#attachmentDoneBtnFullPage',function(){
        widget.currentSelectedFiles = [];
        $('#fileAttachmentBoxFullPage input:checked').each(function() {
            widget.currentSelectedFiles.push($(this).val());
        });
        $('#fileAttachmentBoxFullPage').hide();
        $('#fileAttachmentBtnFullPage').find('.fileAttachmentCountFullPage').remove();
        if(widget.currentSelectedFiles.length > 0){
            $('#fileAttachmentBtnFullPage').append('<span class="fileAttachmentCountFullPage">'+widget.currentSelectedFiles.length+'</span>');
        }
    });
    $(document).on('click','#fileAttachmentCloseBtnFullPage',function(){
        $('#fileAttachmentBoxFullPage').hide();
        $('#fileAttachmentBtnFullPage').find('.fileAttachmentCountFullPage').remove();
        if(widget.currentSelectedFiles.length > 0){
            $('#fileAttachmentBtnFullPage').append('<span class="fileAttachmentCountFullPage">'+widget.currentSelectedFiles.length+'</span>');
        }
    });
    $(document).on('click','#fileAttachmentBoxFullPage input[type=checkbox]',function(){
        if($('#fileAttachmentBoxFullPage input:checked').length > 0){
            $('#attachmentDoneBtnFullPage').removeAttr('disabled');
        }else{
            $('#attachmentDoneBtnFullPage').attr('disabled','true');
        }
    });
</script>
@stop
