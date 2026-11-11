<style>
    .messages-section ul li p{
        min-width: auto !important;
    }
    .messages-thread li.active{
        background-color: #215eac;
        color: #fff;
    }
    .messages-thread li.active .profile-letter-image{
        background-color: #fff;
        color: #215eac;
    }
    .tabs-container .tab-content{
        overflow-y: hidden;
    }
</style>
<div class="col-md-12 p-0 d-flex">
    <div class="col-md-3 messages-thread">
        <p><strong>{{$clinicianDetails->fullName}}'s Conversations</strong></p>
        <ul>
            Fetching messages thread...
        </ul>
    </div>
    <div id="candidateClinicianMessages" class="col-md-9 messages-section hide">
        <ul>
            Fetching Messages...
        </ul>
        <button class="btn primary-btn pull-right mt-2" id="continueConversation">Continue Conversation</button>
    </div>
</div>
