<div class="accordion" id="ipeaChecklistAccordion">
    @foreach ($ipeaChecklist as $k => $group)
    <div class="card">
        <div class="card-header" id="{{str_replace(['/',' '],'',$k)}}">
            <h2 class="mb-0">
                <button class="btn btn-block text-left text-capitalize" type="button" data-toggle="collapse" data-target="#{{str_replace(['/',' '],'',$k)}}One" aria-controls="{{str_replace(['/',' '],'',$k)}}One">
                    {{$k}} <i class="fa fa-chevron-right"></i>
                    <div class="ipea-checklist-counter d-inline">
                        (<span class="checked-counter">0</span> /
                        <span class="total-counter">{{count($group)}}</span>)
                    </div>
                </button>
            </h2>
        </div>

        <div id="{{str_replace(['/',' '],'',$k)}}One" class="collapse checklist" aria-labelledby="{{str_replace(['/',' '],'',$k)}}One" data-parent="#ipeaChecklistAccordion">
            <div class="card-body">
                <ul class="list-group w-100">
                    @foreach ($group as $kk => $checklist)
                        <li class="d-flex border-bottom">
                            <label class="align-self-center" for="{{$checklist->id}}">{{$checklist->checklist}}</label>
                            <input {{in_array($checklist->id,$currentChecklists) ? 'checked="checked"' : ''}} class="form-control ml-auto" type="checkbox" name="checklist[]" id="{{$checklist->id}}" value="{{$checklist->id}}">
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endforeach
</div>

<script>
    $(document).ready(function() {
        $(document).off('change','input[name="checklist[]"]');
        $(document).on('change','input[name="checklist[]"]',function() {
            var section = $(this).closest('.card');
            var checkedCheckboxes = section.find('input[type="checkbox"]:checked');
            section.find('.checked-counter').html(checkedCheckboxes.length);
            $('.selectedChecklistTotal').html($('#ipeaChecklistAccordion').find('input[type="checkbox"]:checked').length);
            saveChecklist();
        });

        //Checked current checkboxes
        $('#ipeaChecklistAccordion .card').each(function(i, e) {
            var checkedCheckboxes = $(this).find('input[type="checkbox"]:checked');
            $(this).find('.checked-counter').html(checkedCheckboxes.length);
            $('.selectedChecklistTotal').html($('#ipeaChecklistAccordion').find('input[type="checkbox"]:checked').length);
        });
    });
    function saveChecklist() {
        var ids = [];
        $('input[name="checklist[]"]:checked').each(function(i, e) {
            ids.push($(this).val());
        });
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('ipea.saveChecklist')}}",
            type: 'post',
            data: {
                scheduleId: $('#scheduleId').val(),
                trackerId: $('#trackerId').val(),
                checklist: ids.join(),
                _token: _token
            },
            success: function(data) {
                if (isNeoTemplateOpened === false) {
                    reloadIpeaHistory($('#trackerId').val());
                }else{
                    reloadIpeaNeoHistory($("#scheduleId").val());
                }
            }
        });
    }
</script>
