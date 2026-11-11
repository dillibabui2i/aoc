<x-modal>
    <x-slot name="id">getConversationDetailModal</x-slot>
    <x-slot name="size">w-75</x-slot>
    <x-slot name="title">Conversation Detail</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
    <div class="col-md-12 p-0 mt-3 d-inline-block align-top">
        <div id="candidateClinicianMessages" class="col-md-9 messages-section">
            <ul>
                @foreach($messages as $messagesData)
                @php
                $messageClass  = 'right';
                if(isset($messagesData['user_id']) && $messagesData['user_id'] != '' && $messagesData['user_id'] !=  0){
                    $messageClass  = 'left';
                }
                @endphp
                <li data-message-id="{{ $messagesData['id'] }}" class="{{$messageClass}}"><p>{{ $messagesData['message'] }}</p></li>
                @endforeach
            </ul>
        </div>
    </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>