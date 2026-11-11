<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $steps = H::getHelper('ClientCareCoordinatorCoverage')->getBucketArray();
?>

<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<x-modal>
    <x-slot name="id">coverageDetailsModal</x-slot>
    <x-slot name="size">w90</x-slot>
    <x-slot name="title">Coverage Details /
        {{(isset($patientsDetails->Patient_FirstName) ? $patientsDetails->Patient_FirstName : '').' '.(isset($patientsDetails->Patient_LastName) ? $patientsDetails->Patient_LastName : '')}}
    </x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
        </div>
        <div class="col-md-6 text-right pull-right">
            @if(LD::checkFeatureFlag('allow-user-change-bucket-of-tracker'))
            <span id="coverageStepSpan" class='editable-select-coverage-step btn btn-primary' data-emptytext='Click to Change Candidate Current Status'
                data-type='select'
                data-source='<?php echo htmlspecialchars(json_encode($steps),ENT_QUOTES, 'UTF-8');?>'
                data-value='{{ nl2br($coverageDetail->last_activity) }}' data-url='{{route("editable")}}'
                data-pk="{{ $coverageDetail->id }}" data-name='last_activity'
                data-table='maps_coverageneeds'>
                {{ nl2br($coverageDetail->last_activity) }}
            </span>
            @endif
        </div>
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-9 tracker-details">
                <div class="col-md-12 d-flex p-0">
                    <div class="col-md-6 p-0">
                    </div>
                    <div class="col-md-6 p-0">
                    </div>
                </div>
                <div class="col-md-12 card-cnt p-0 d-flex">
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Client</h5>
                            <div class="detail-section">
                                <h5>
                                    <?php $name='';
                                             if(isset($patientsDetails->Patient_FirstName)) {
                                                $name.=$patientsDetails->Patient_FirstName;
                                             }
                                             if(isset($patientsDetails->Patient_MiddleName)) {
                                                $name.=" ".$patientsDetails->Patient_MiddleName;
                                             }
                                             if(isset($patientsDetails->Patient_LastName)) {
                                                $name.=" ".$patientsDetails->Patient_LastName;
                                             }
                                             echo trim($name);
                                            ?>
                                </h5>
                                <p class="card-text">
                                    {{isset($patientsDetails->Patient_City) ? $patientsDetails->Patient_City : ""}} {{isset($patientsDetails->Patient_State) ? $patientsDetails->Patient_State : ""}}</p>

                                <p class="card-text">
                                    {{isset($patientsDetails->Patient_Phone) ? $patientsDetails->Patient_Phone : ""}}
                                </p>
                                <p class="card-text">
                                    <span id="client-card-mobile">
                                        {{isset($patientsDetails->Patient_MobilPhone) && $patientsDetails->Patient_MobilPhone != '' ? $patientsDetails->Patient_MobilPhone.' (Mobile)' : ""}}
                                    </span>
                                </p>
                                <p class="card-text">
                                    {{isset($patientsDetails->Patient_Address1) ? $patientsDetails->Patient_Address1 : ""}},{{isset($patientsDetails->Patient_Address2) ? $patientsDetails->Patient_Address2 : ""}}
                                </p>
                                <p class="card-text">
                                    {{isset($patientsDetails->Patient_City) ? $patientsDetails->Patient_City : ""}}
                                    {{isset($patientsDetails->Patient_State) ? $patientsDetails->Patient_State : ""}}
                                    {{isset($patientsDetails->Patient_Zip) ? $patientsDetails->Patient_Zip : ""}}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Coverage Details</h5>
                            <div class="detail-section">

                            </div>
                        </div>
                    </div>
                </div>


                <div class="col-md-12 p-0 mt-3">
                    <table class="table" aria-describedby="">
                        <tr>
                            <th>Coverage Steps</th>
                            <th>Date</th>
                            <th>Number</th>
                        </tr>
                        @foreach($steps as $key => $step)
                        @php
                        $activityDateField = $key.'_activity_date';
                        $numberField = $key.'_number';
                        @endphp
                        <tr>
                            <td>{{$step}}</td>
                            <td>{{ (isset($coverageDetail->{$activityDateField})) ? H::getFormatedDatetime($coverageDetail->{$activityDateField}):'' }}</td>
                            <td>{{ (isset($coverageDetail->{$numberField})) ? $coverageDetail->{$numberField}:'' }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            <div class="col-md-3 d-flex p-0">
                <x-activity-log>

                </x-activity-log>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer">
    </x-slot>
</x-modal>
<script>
    function reloadTriageActivity(id, type, destination) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('coverage.getCoverageTrackerActivity')}}",
            type: 'get',
            data: {
                id: id,
                type: type,
                _token: _token
            },
            dataType: 'html',
            success: function(data) {
                $(destination).html(data);
            }
        });
    }
    $(document).ready(function() {
        reloadTriageActivity('<?php echo $coverageDetail->id?>', 'coverage', '.activity-log');
    });
</script>
