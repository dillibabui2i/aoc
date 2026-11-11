<?php
    use Illuminate\Support\Facades\Auth;
    use App\Helpers\H;

    $action = Route::getCurrentRoute()->getActionName();
    $actionParts = explode("@", $action);
    $actionName = isset($actionParts[1]) ? $actionParts[1] : $action;
    $allAuthor = H::getHelper('ConstantHelper')->getTwilioUsersNumber();
    $twilioUsers = H::getHelper('ConstantHelper')->getTwilioUsersWithName();

?>
<?php if(Auth::user()->sending_sms_number == ''){?>
    <style>
        .chat-btn,#startConversationWithCandidateBtn,#startConversationWithClinicianBtn,.joinConversationBtn,.takeOverConversationBtn,#continueConversation{
            display: none !important;
        }
    </style>
<?php } ?>
<script src="https://media.twiliocdn.com/sdk/js/conversations/releases/2.2.0/twilio-conversations.min.js" integrity="sha256-IVvvlFjCqVxfaKZj7uHAVoOwBtl5q5EjGz4KScEIN+4=" crossorigin="anonymous"></script>
<script>
    let _t = $('meta[name="csrf-token"]').attr('content');
    let token = "";
    const widget = {
        allMessageCount:0,
        allConversations:[],
        currentSelectedFiles:[],
        currentConversation:'',
        author: "<?php echo Auth::user()->sending_sms_number; ?>",
        allAuthorNumber: "<?php echo $allAuthor; ?>",
        fromNumber:"<?php echo Auth::user()->sending_sms_number; ?>",
        openRequestedConversation:'',
        widgetType: "<?php echo $actionName == 'fullPageView' ? '-full-page' : ''?>",
        twilioUsers: JSON.parse('<?php echo $twilioUsers; ?>'),
        getFormattedNumber: function(phoneNumber) {
            phoneNumber = phoneNumber.replace('+1', '');
            phoneNumber = phoneNumber.replace(/[^0-9]/g, '');
            return "+1"+phoneNumber.replace(/(\d{3})(\d{3})(\d{4})/, "($1) $2-$3");
        },
        ajaxCall: async function(url,type,data){
            try{
                return await $.ajax({
                    url: url,
                    type: type,
                    data: data
                });
            }catch(e){
                console.log('ajaxCall:',e);
            }
        },
        /**
         * options: options will be the object like: {logLevel: 'trace'}
        */
        getClient: async function (options){
            try{
                if(token == ''){
                    var data = await widget.ajaxCall("{{route('messageWidget.getToken')}}","GET",{_token: _t});
                    token = data.token;
                }
                options = typeof options === 'undefined' ? {} : options;
                return new Twilio.Conversations.Client(token,options);
            }catch(e){
                console.log('getClient:',e);
            }
        },

        /**
         * Update the token when its about to expired
        */
        onTokenAboutToExpire: async function(){
            const client = await widget.getClient();
            client.on("tokenAboutToExpire", async (time) => {
                // token is about to expire. get a new token
                try {
                    var data = await widget.ajaxCall("{{route('messageWidget.getToken')}}","GET",{_token: _t});
                    token = data.token;
                    console.log(token,'new token');
                } catch {
                    return Error("Unable to get a token");
                }
            });
        },
        /**
         * message: get message object when message added to the conversation
        */
        lastUserForOnMsg:'',
        parseMessageToCheckUrlAndFiles: function(msg){
            if(msg != null && msg != '' && msg.indexOf('http') != -1){
                if(widget.getParameterByName('name',msg) == null){
                    var urlRegex = /(https?:\/\/[^\s]+)/g;
                    msg = msg.replace(urlRegex, function(url) {
                        return '<a target="_blank" href="' + url + '">' + url + '</a>';
                    });
                }else{
                    msg ='<a href="'+msg+'">'+widget.getParameterByName('name',msg)+"</a>";
                }
            }
            return msg;
        },
        onMessageAdd: async function (message){
            if(widget.currentConversation != '' && message.conversation.sid == widget.currentConversation.sid){
                let messageStr = '';
                const messageClass = (message.author === widget.author.replace('+','')) ? 'right' : 'left';
                let msg = widget.parseMessageToCheckUrlAndFiles(message.body);
                // Checking the Today tag is there or not
                if($('.user-messages'+widget.widgetType+' ul span.msg-date').eq($('.user-messages'+widget.widgetType+' ul span.msg-date').length-1).html()
 != 'Today'){
                    messageStr += '<span class="msg-date">Today</span>';
                }

                if(message.author !== widget.author.replace('+','') && widget.lastUserForOnMsg !== message.author && message.conversation.uniqueName.indexOf(message.author+'#') === -1){
                    messageStr += '<span class="msg-user">'+widget.twilioUsers['+'+message.author]+'</span>';
                }else if(message.conversation.uniqueName.indexOf(message.author+'#') !== -1 && widget.lastUserForOnMsg !== message.author){
                    messageStr += '<span class="msg-user">'+message.conversation.friendlyName+'</span>';
                }
                widget.lastUserForOnMsg = message.author;
                messageStr += '<li data-message-id="'+message.sid+'" class="'+messageClass+'"><p>'+msg+'<span class="msg-time">'+widget.getFormattedTime(message.dateCreated)+'</span></p></li>';
                $('.user-messages'+widget.widgetType+' ul').append(messageStr);
            }

            //Refreshing the count of the unread messages
            if(message.conversation.sid != widget.currentConversation.sid){
                const messageCount = await message.conversation.getUnreadMessagesCount();
                console.log(messageCount,'messageCount:onMessageAdd');
                if(messageCount > 0){
                    $('.user-list'+widget.widgetType+' ul').find('li[data-conversation-id="'+message.conversation.sid+'"] .unread-message-count').remove();
                    $('.user-list'+widget.widgetType+' ul').find('li[data-conversation-id="'+message.conversation.sid+'"]').append('<span class="unread-message-count">'+messageCount+'</span>');
                    widget.allMessageCount++;
                    widget.setAllMessageCount();
                }
            }
            $('.user-chat-section ul').animate({scrollTop: $('.user-chat-section ul').prop('scrollHeight')}, "slow");
        },
        /**
         * id: Conversation Id which start with CHXXXX....
         * client: Client object of the twilio
        */
        getConversationsById: async function(id,client){
            try{
                return await client.getConversationBySid(id);
            }catch(e){
                console.log('getConversationsByUserId:',e);
            }
        },
        /**
         * conversation : Current conversation object
         * proxyAddress: Twilio phone number for use as FROM
         * address: User phone number
         * attributes: Extra attribute like unique identity
         */
        addParticipant: async function(conversation,proxyAddress,address,attributes){
            try{
                attributes = typeof attributes !== 'undefined' ? attributes : { identity: address };
                await conversation.addNonChatParticipant(proxyAddress, address,attributes);
            }catch(e){
                console.log('addParticipant:',e);
            }
        },
        /**
         * conversation : Current conversation object
         * text: string of the message
        */
        sendMessage: async function(conversation,text){
            try {
                await conversation.sendMessage(text);
                if(widget.currentSelectedFiles.length > 0){
                    console.log(widget.currentSelectedFiles,'widget.currentSelectedFiles');
                    widget.currentSelectedFiles.forEach(async function(fileUrl) {
                        conversation.sendMessage(fileUrl);
                    });
                    widget.currentSelectedFiles = [];
                    $('#fileAttachmentBtn').find('.fileAttachmentCount').remove();
                }
            } catch (e) {
                console.log('sendMessage:',e);
            }
        },
        disableButtons: function(){
            $('#userMessage').attr('disabled','true');
            $("#sendMessageBtn").addClass('pe-none');
            $("#sendMessageBtn .fa-paper-plane").addClass('hide');
            $("#sendMessageBtn .fa-spinner").removeClass('hide');
        },
        enableButtons: function(){
            $('#userMessage').removeAttr('disabled');
            $("#sendMessageBtn").removeClass('pe-none');
            $("#sendMessageBtn .fa-paper-plane").removeClass('hide');
            $("#sendMessageBtn .fa-spinner").addClass('hide');
        },
        disableButtonsFullPage: function(){
            $('#userMessageFullPage').attr('disabled','true');
            $("#sendMessageBtnFullPage").addClass('pe-none');
            $("#sendMessageBtnFullPage .fa-paper-plane").addClass('hide');
            $("#sendMessageBtnFullPage .fa-spinner").removeClass('hide');
        },
        enableButtonsFullPage: function(){
            $('#userMessageFullPage').removeAttr('disabled');
            $("#sendMessageBtnFullPage").removeClass('pe-none');
            $("#sendMessageBtnFullPage .fa-paper-plane").removeClass('hide');
            $("#sendMessageBtnFullPage .fa-spinner").addClass('hide');
        },
        formatPhoneForMessage: function(phone){
            phone = phone.replace(/[^\d\+]/g,"");
            if(phone.indexOf('+') !== -1){
                return phone;
            }else{
                if(phone.length == 10){
                    return "+1"+phone;
                }else if(phone.length == 11){
                    return "+"+phone;
                }else{
                    return '';
                }
            }
        },
        openWidgetWindow: function(){
            $("#chatWidgetIcon").click();
        },
        openConversation: async function(conversation){
            $('#backToUserList').click();
            if($('.user-list ul').find('li[data-conversation-id="'+conversation.sid+'"]').length > 0){
                $('.user-list ul').find('li[data-conversation-id="'+conversation.sid+'"]').click();
            }else{
                //Refreshing the Conversation list
                widget.openRequestedConversation = conversation;
                $('.user-list ul').html('Refreshing the list...');
                await loadConversationToWidget();
            }
        },
        closeModalPopup:function(){
            $('.modal').modal('hide');
        },
        setAllMessageCount: function(){
            if(widget.allMessageCount > 0){
                $('.chat-btn').find('.all-message-count').remove();
                $('.chat-btn').append('<span class="all-message-count">'+widget.allMessageCount+'</span>')
            }else{
                $('.chat-btn').find('.all-message-count').remove();
            }
        },
        setUnreadMessagesCount:async function(conversation){
            const messageCount = await conversation.getUnreadMessagesCount();
            widget.allMessageCount += messageCount;
            if(messageCount > 0){
                $('.user-list'+widget.widgetType+' ul').find('li[data-conversation-id="'+conversation.sid+'"]').append('<span class="unread-message-count">'+messageCount+'</span>');

            }
            widget.setAllMessageCount();
        },
        /**
         * options: options will be the object like: {logLevel: 'trace'}
        */
        loadFileSelectionList: async function (selector,userType){
            try{
                if(typeof userType !== 'undefined' && userType != ''){
                    var data = await widget.ajaxCall("{{route('messageWidget.getFileList')}}","GET",{_token: _t, userType:userType});
                    $(selector).html(data);
                }
            }catch(e){
                console.log('loadFileSelectionList:',e);
            }
        },
        getParameterByName: function (name, url) {
            name = name.replace(/[\[\]]/g, '\\$&');
            var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
                results = regex.exec(url);
            if (!results) return null;
            if (!results[2]) return '';
            return decodeURIComponent(results[2].replace(/\+/g, ' '));
        },
        addParticipantAndLoadWidget: async function (conversationId){
            try {
                const client = await widget.getClient();
                client.on('initialized', async() => {
                    const conversation = await widget.getConversationsById(conversationId,client);
                    conversation.add(widget.author.replace('+',''));
                    widget.closeModalPopup();
                    widget.openWidgetWindow();
                    widget.openConversation(conversation);
                });
            } catch (error) {
                console.log(error);
            }
        },
        setConversationToReadToDB: async function (conversationId){
            try {
                if(conversationId != ''){
                    await widget.ajaxCall("{{route('messageWidget.setConversationToRead')}}","GET",{_token: _t,conversationId:conversationId});
                }
            } catch (error) {
                console.log(error);
            }
        },
        getAllSubscribedConversations: async function(client){
            let allConversations = [];
            let conversations = await client.getSubscribedConversations();
            while (true) {
                allConversations = allConversations.concat(conversations.items);
                if (conversations.hasNextPage) {
                    conversations = await conversations.nextPage();
                } else {
                    break;
                }
            }
            return allConversations;
        },
        getFormattedDate: function(date){
            const currentDate = new Date();
            if (
                date.getDate() === currentDate.getDate() &&
                date.getMonth() === currentDate.getMonth() &&
                date.getFullYear() === currentDate.getFullYear()
            ) {
                return "Today";
            }

            const yesterday = new Date(currentDate);
            yesterday.setDate(currentDate.getDate() - 1);

            if (
                date.getDate() === yesterday.getDate() &&
                date.getMonth() === yesterday.getMonth() &&
                date.getFullYear() === yesterday.getFullYear()
            ) {
                return "Yesterday";
            }

            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "long",
                day: "numeric",
            });
        },
        getFormattedTime: function(date){
            const dateCreated = new Date(date);
            // Extract time in AM/PM format
            return dateCreated.toLocaleTimeString("en-US", {
                hour: "numeric",
                minute: "numeric",
                // second: "numeric",
                hour12: true,
            });
        },
        joinConversation: async function(obj,dbId,sid){
            $(obj).find('.fa').removeClass('fa-comment-o').addClass('fa-spinner');
            await widget.addParticipantAndLoadWidget(sid);
            await widget.ajaxCall("{{route('messageWidget.addParticipantToConversation')}}","GET",{_token: _t,id:dbId});
            $(obj).find('.fa').removeClass('fa-spinner').addClass('fa-comment-o');
        },
        takeOverConversation: async function(obj,dbId, sid) {
            try {
                $(obj).find('.fa').removeClass('fa-comment-o').addClass('fa-spinner');
                const client = await widget.getClient();
                client.on('initialized', async() => {
                    const conversation = await widget.getConversationsById(sid, client);
                    const participants = await conversation.getParticipants();
                    var isRemoved = false;
                    await Promise.all(participants.map(async (participant) => {
                        if (participant.identity != null && participant.identity != '' && participant.identity != widget.author.replace('+','')) {
                            await participant.remove();
                            isRemoved = true;
                        }
                    }));
                    await widget.addParticipantAndLoadWidget(sid);
                    if(isRemoved){
                        await widget.ajaxCall("{{route('messageWidget.updateUserIdForTakeOverConversation')}}","GET",{_token: _t,id:dbId});
                    }
                    $(obj).find('.fa').removeClass('fa-spinner').addClass('fa-comment-o');
                });
            } catch (error) {
                console.log(error);
            }
        }
    };
</script>
