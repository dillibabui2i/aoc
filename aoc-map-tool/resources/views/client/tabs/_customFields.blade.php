<?php
    use App\Helpers\H;
    use App\Helpers\Ld;
    $categoryCount = count($customFields);
    $catCount = 1;
?>
<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>

@foreach($customFields as $category => $fields)
    @if ($category != 'Default')
        <div class="row ml-0"><h3>{{$category}}</h3></div>
    @endif
    <div class="row {{($catCount != $categoryCount) ? 'border-bottom mb-3' : ''}}">
        @foreach($fields as $key => $field)
            @if (!empty($field))
                <div class="col-md-6 pt-1 pb-1">
                    {!! $field !!}
                </div>
            @endif
        @endforeach
        @php
            $catCount++;
        @endphp
    </div>
@endforeach
<script type="text/javascript">
    $(document).ready(function() {
        @if(!H::canPermissions('editCustomFieldValue'))
            $('#customFields .editable').editable('disable');
        @endif
    });
</script>
