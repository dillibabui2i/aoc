<?php
    use App\Helpers\H;
?>
<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<section class="tab-content-preferences">
    <div class="main-content">
        <div class="tab-content-area col-md-12">
            <div class="row">

                @foreach($preferencefields as $key => $field)
                    @if (!empty($field))
                        <div class="col-md-6 pt-1 pb-1">
                            {!! $field !!}
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</section>
<script type="text/javascript">
    $(document).ready(function() {
        @if(!H::canPermissions('editCustomFieldValue'))
            $('.editable').editable('disable');
        @endif
    });
</script>

