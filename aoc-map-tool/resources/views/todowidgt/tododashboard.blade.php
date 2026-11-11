<?php

use App\Enums\TodoSourceEnum;
use App\Helpers\H;
use App\Helpers\LD;
use App\Notes;

$createdByUser = H::getHelper("TodoWidgt")->getNoteCreatedUser();
$Status = [['value' => '1', 'text' => 'All']];
$entitys = [];
foreach (Config('constant.todoAvailableEntity') as $key => $data) {
    $entitys[$key] = ['value' => $data, 'text' => $data];
}
$taskFilter = [
    [
        'label' => 'Me Mode',
        'name' => 'me_mode',
        'type' => 'checkbox',
        'value' => true
    ],
    [
        'label' => 'Entity',
        'name' => 'entity',
        'type' => 'multiselect',
        'source' => $entitys
    ],
    [
        'label' => 'Created By',
        'name' => 'created_by',
        'type' => 'multiselect',
        'source' => $createdByUser
    ],
    [
        'label' => 'Source',
        'name' => 'source',
        'type' => 'multiselect',
        'source' => [
            ['value' => TodoSourceEnum::AOC_PLATFORM->value, 'text' => TodoSourceEnum::AOC_PLATFORM->value],
            ['value' => TodoSourceEnum::AOC_MOBILE->value, 'text' => TodoSourceEnum::AOC_MOBILE->value],
        ]
    ],
];

?>
<div class="card">
    <div class="card-header" id="todo">
        <h2 class="mb-0">
            <button class="col-md-11 pull-left btn btn-link btn-block text-left collapsed" type="button"
                    data-toggle="collapse"
                    data-target="#todo_childs" aria-expanded="false" aria-controls="collapseThree">
                To Do<i class="fa fa-chevron-right"></i>
            </button>
        </h2>
    </div>
    <div id="todo_childs" class="collapse" aria-labelledby="headingThree" data-parent="#todo_childs">
        <div class="card-body">
            <div class="card">
                <div class="card-header" id="task-list">
                    <h3 class="mb-0">
                        <button class="col-md-11 pull-left btn btn-link btn-block text-left collapsed" type="button"
                                data-toggle="collapse" data-target="#task_list" aria-expanded="false"
                                aria-controls="collapseOne">
                            Task List <span id="task_list-counter"></span><i class="fa fa-chevron-right"></i>
                        </button>
                    </h3>
                </div>
                <div id="task_list" class="collapse " aria-labelledby="dailyactivityone" data-parent="#task_list">
                    <div class="filters-common">
                        @component('components.datatable-filters',['filters'=>$taskFilter,'formname'=>'tasklistform'])
                        @endcomponent
                    </div>
                    <div class="card-body">
                        @php echo H::getHelper("TodoWidgt")->getDataTableHtml("task_list") @endphp
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>

    const taskBucketArray = ['task_list'];
    $(document).on('submit', '#tasklistform', function (e) {
        e.preventDefault();
        var serializeData = $("#tasklistform").serializeArray();
        for (var i = 0; i < taskBucketArray.length; i++) {
            var tempObject = taskBucketArray[i].replace(/ /g, "_");
            if ($("#reload-" + tempObject).val() == 1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('dashboard.reloadCounttodotasklist') }}?type=" + taskBucketArray[i],
                    type: "POST",
                    data: {
                        filters: serializeData,
                        _token: _token
                    },
                    success: function (response) {
                        var jsonObject = JSON.parse(response);
                        var type = jsonObject.type;
                        var idObject = type.replace(/ /g, "_");
                        $("#" + idObject + '-counter').html(jsonObject.counter);
                    },
                });
            } else {
                console.log("tasklistform=tempObject=" + tempObject);
                $("#dataTable-task_list-" + tempObject).DataTable().ajax.reload();
            }
        }
    });
    $("#tasklistform").trigger("submit");
</script>
