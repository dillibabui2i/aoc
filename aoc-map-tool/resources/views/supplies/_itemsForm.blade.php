@if($count < 1)
<div class="supplies-form-inner mt-5">
@else
<div class="supplies-form-inner mt-3">
@endif
    <div class="form-group row">
        <div class="col-md-4">
            @if($count <= 1)
            <label class="w-100">Item</label>
            @endif
            <select name="item{{$count}}" class="form-control" id="item{{$count}}">
                <option value="">--- Select ---</option>
                @foreach(config('constant')['suppliesItems'] as $v)
                <option value="{{$v}}">{{$v}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
        @if($count <= 1)
            <label  class="w-100">Description</label>
        @endif
            <input type="text" class="form-control" id="description{{$count}}" name="description{{$count}}" value=""/>
        </div>
        <div class="col-md-3">
        @if($count <= 1)
            <label  class="w-100">Qty</label>
        @endif
            <input type="text" class="form-control" id="qty{{$count}}" name="qty{{$count}}" value=""/>
        </div>
        @if($count > 0)
            <div class="col-md-1 removebtn d-flex">
                <a href="javascript:void(0);" class='supplies-remove-icon align-self-end'>
                    <i class="fa fa-times-circle-o"></i>
                </a>
            </div>
        @endif
    </div>
</div>
