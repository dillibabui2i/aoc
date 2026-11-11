@foreach($data as $k => $v)
    <li class="list-group-item">
        <input data-id="{{$v->id}}" data-href="{{route('managemessageattachment.download')}}?id={{$v->id}}" type="checkbox" name="attachedFile[]" id="{{$v->id}}" value="{{route('managemessageattachment.download')}}?id={{$v->id}}&name={{$v->name}}" />
        <label for="{{$v->id}}">{{$v->name}}</label>
    </li>
@endforeach
