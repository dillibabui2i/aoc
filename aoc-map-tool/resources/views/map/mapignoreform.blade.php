<?php
use App\Helpers\H;
use App\Helpers\LD;

$maps = H::getHelper("ConstantHelper")->getAllMaps();
?>
@extends('layouts.myaccount')
@section('title', 'Manage Map Ignore')
@section('content')
<!-- Begin Page Content -->

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage Map Ignore</h1>
            <a href="{{ route('map.ignoregrid') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
        </div>
    </div>
    <!-- DataTales Example -->
    @if ($errors->any())
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {!! implode('', $errors->all('<div>:message</div>')) !!}
    </div>
    @endif
    @if ($message = Session::get('error'))
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ $message }}
    </div>
    @endif
    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ $message }}
    </div>
    @endif
    <div class="mapignoreform">
        <form method="POST" id="add-form-validate" enctype='multipart/form-data' action="{{ route('map.saveignore') }}">
            <input type="hidden" name="id" id="id" value="<?php echo isset($data->id)?$data->id:''?>">
                @csrf
                <div id="append-data" class="form-row">
                    <div class="form-group col-md-12">
                        <label for="branch" class="text-left col-md-3">Map</label>
                        <select name="map_id" id="map_id"  class="form-control login">
                        <?php foreach($maps as $map){ ?>
                                <option data-unique='<?php echo $map->unique_identifier_col?>' value="<?php echo $map->map_id?>" <?php if(isset($data->map_id) && $data->map_id==$map->map_id) { ?> selected="selected" <?php }?>><?php echo $map->map_name?></option>
                        <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="branch" class="text-left col-md-3">Map Search</label>
                        <input type="text" class="form-control form-group" name="map-search" id="map-search" value="" />
                    </div>
                </div>

                <div id="table-structure" class="col-md-12" >
                    
                    <table width="100%" cellpadding="5" cellspacing="5"  id="table-response">
                    </table> 
                </div>
                <div class="col-md-12" id="instruction-note" style="display:none;">Before hitting Save button. Make sure you have choosen row above, which you want to ignore.</div>
                <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
        </form>
    </div>
</div>

<script>
    var selectedIdentifier = "";
    <?php
        if(!empty($data)) { ?>
        selectedIdentifier = "<?php echo htmlspecialchars(json_encode($data->unique_identifier),ENT_QUOTES, 'UTF-8'); ?>";
        <?php }
    ?>
    $(document).ready(function(){
        $(document).on("submit","#add-form-validate",function(){
            if($(".selectedrow").length){
                return true;
            } else {
                return false;
            }
        });
        $(document).on("change","#map_id",function(){
           var unique =  $(this).find(':selected').attr('data-unique');           
            const jsonObject= jQuery.parseJSON((unique != '') ? unique : JSON.stringify(''));
            $("#append-data .dynamicrow").remove();
            $.each(jsonObject, function( index, value ) {
                $("#append-data").append('<div class="form-group col-md-12 dynamicrow"><label class="text-left col-md-3">'+value+'</label><input class="form-control form-group uniquesearch required" type="text" id="unique-'+value+'" name="unique['+value+']"   /></div>');    
            });
        });
        $("#map_id").trigger("change");

        (function($) {
            // Empty object, we are going to use this as our Queue
            var ajaxQueue = $({});

            $.ajaxQueue = function(ajaxOpts) {
                // hold the original complete function
                var oldComplete = ajaxOpts.complete;

                // queue our ajax request
                ajaxQueue.queue(function(next) {    

                // create a complete callback to fire the next event in the queue
                ajaxOpts.complete = function() {
                    // fire the original complete if it was there
                    if (oldComplete) oldComplete.apply(this, arguments);    
                    next(); // run the next query in the queue
                };

                // run the query
                $.ajax(ajaxOpts);
                });
            };

        })(jQuery);

        $(document).on("keyup","#map-search, .uniquesearch",function(){
            var val = $(this).val();
            var unique =  $("#map_id").find(':selected').attr('data-unique');
            const uniqueObject= jQuery.parseJSON((unique != '') ? unique : JSON.stringify(''));
            $.ajaxQueue({
                url: "{{route('map.performglobalsearch')}}",
                method: "get",
                async: true,
                data: $("#add-form-validate").serialize(),
                success: function(data) {
                    var jsonObject = jQuery.parseJSON(data);
                    var text = "";
                    $("#instruction-note").show();
                    $.each(jsonObject, function(index,jobj){
                        var valueArray = [];
                        var counter = 0;
                        $.each(uniqueObject, function( uindex, uvalue ) {
                            valueArray[counter] = jobj[uvalue];
                            counter++;
                        });
                        text+= '<tr class="result-row" data-unique="'+valueArray.join("###")+'">';
                        $.each(jobj,function(i,j){
                            text+="<td nowrap>"+i+":"+j+"</td>";
                        });
                        text+="</tr>";
                    });
                    $("#table-response").html(text);
                }
            });
        });

        $(document).on("click",".result-row",function(){
            var val = $(this).attr("data-unique").split("###");
            $(".result-row").removeClass("selectedrow");
            $(this).addClass("selectedrow");
            var unique =  $("#map_id").find(':selected').attr('data-unique');
            const uniqueObject= jQuery.parseJSON((unique != '') ? unique : JSON.stringify(''));
            var counter = 0;
            $.each(uniqueObject, function( uindex, uvalue ) {
               $("#unique-"+uvalue).val(val[counter]);
               counter++;
            });
        });

        $("#add-form-validate").validate({
            errorPlacement: function() {
                return true;
            },
        });
    });
</script>
@stop
