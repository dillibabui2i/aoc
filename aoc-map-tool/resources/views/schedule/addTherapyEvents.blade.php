@php
use App\Helpers\LD;
use App\Helpers\H;
@endphp
<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">

<x-modal>
    <x-slot name="id">addTherapyIpeaEvent</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">{{ ($mode == 'edit') ? 'Edit Therapy NEO' : 'Add Therapy NEO' }}</x-slot>

    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div id="therapyEventSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" data-dismiss="modal" aria-label="Close" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div id="therapyEventFormDiv">
            <form action="{{route('schedules.savetherapyipeaevent')}}" method="POST" id="therapyEventForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type_id" value="998" />
                @if($mode == 'edit' || $mode == 'reschedule')
                <input type="hidden" name="id" value="{{$data->id}}" />
                @endif
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for=""> NEO Location <abbr class="text-danger" title="This is required">*</abbr> </label>
                        <select required class="form-control" name="ipea_location_id" id="ipea_location_id">
                            <option value=""> Select Branch </option>
                            @foreach($branches as $key => $branch)
                            <optgroup label="{{$key}}">
                                @foreach($branch as $b)
                                <option {{((isset($lastCalendarSearch['branch']) && $b['id'] == $lastCalendarSearch['branch']) || (($mode == 'edit' || $mode == 'reschedule') && $b['id'] == $data->ipea_location_id)) ? 'selected="selected"' : ''}} value="{{$b['id']}}">{{$b['branch']}}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please Select Branch. This field is required</div>
                    </div>


                    <div class="col-md-6">
                        <label for=""> NEO Date<abbr class="text-danger" title="This is required">*</abbr> </label>
                        <input type="date" required class="form-control" name="appointment_date" id="appointment_date" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->appointment_date : ''}}" />
                        <div class="invalid-feedback">Please enter your Appointment Date. This field is required</div>
                    </div>
                </div>
                @if(LD::checkFeatureFlag('add-new-form-filed-in-therapy-neo-form'))
                <div class="form-group row">
                    <div class="col-md-6 bilingualclass">
                        <div class="custom-control custom-switch custom-control-right">
                            <input type="hidden" name="bilingual" value="0" />
                            <input class="custom-control-input" type="checkbox" id="bilingual" name="bilingual" value="1" {{($mode == 'edit' || $mode == 'reschedule') ? ($data->Bilingual == 1) ? 'checked' : '' : ''}}/>
                            <label class="custom-control-label" for="bilingual">Bilingual?</label>
                        </div>
                    </div>
                </div>
                @endif
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for=""> NEO Time<abbr class="text-danger" title="This is required">*</abbr> </label>
                        <input type="text" required class="form-control" name="appointment_time" id="appointment_time" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->appointment_time : ''}}" />
                        <div class="invalid-feedback">Please enter your Appointment Time. This field is required</div>
                    </div>

                    <div class="col-md-6">
                        <label for=""> First Name</label>
                        <input type="input" class="form-control" name="first_name" id="first_name" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->first_name : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}} />
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for=""> Last Name </label>
                        <input type="text" class="form-control" name="last_name" id="last_name" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->last_name : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}} />
                    </div>
                    <div class="col-md-6">
                        <label for="discipline">Discipline</label>
                        <select id="discipline" name="discipline" class="form-control" autocomplete="off" {{($mode == 'reschedule') ? 'readonly' : ''}}>
                            <option value="">-- Select One --</option>
                            <option value="BCBA" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'BCBA') ? 'selected' : ''}}>BCBA</option>
                            <option value="BT" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'BT') ? 'selected' : ''}}>BT</option>
                            <option value="OT" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'OT') ? 'selected' : ''}}>OT</option>
                            <option value="OTA" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'OTA') ? 'selected' : ''}}>OTA</option>
                            <option value="PT" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'PT') ? 'selected' : ''}}>PT</option>
                            <option value="PTA" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'PTA') ? 'selected' : ''}}>PTA</option>
                            <option value="RBT" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'RBT') ? 'selected' : ''}}>RBT</option>
                            <option value="ST" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'ST') ? 'selected' : ''}}>ST</option>
                            <option value="STA" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'STA') ? 'selected' : ''}}>STA</option>
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="phone">Phone No</label>
                        <input type="input" class="form-control" id="phone" name="phone" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->phone : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}>
                    </div>
                    <div class="col-md-6">
                        <label for="email">Email</label>
                        <input type="input" class="form-control" id="email" name="email" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->email : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}>
                    </div>

                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="zipcode">Zip Code</label>
                        <input type="input" class="form-control" id="zipcode" name="zipcode" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->zipcode : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}>
                    </div>


                    <div class="col-md-6">
                        <label for="recruiter">Recruiter </label>
                        <select id="recruiter" name="recruiter" class="form-control" autocomplete="off" {{($mode == 'reschedule') ? 'readonly' : ''}}>
                            <option value="">-- Select One --</option>
                            @php
                                $therapyRecruiters = H::getHelper("ConstantHelper")->getUsersByRoles(['Therapy Recruiter']);
                            @endphp
                            @foreach($therapyRecruiters as $recruiterName)
                                <option value="{{$recruiterName['text']}}" {{(($mode == 'edit' || $mode == 'reschedule') && $data->recruiter == $recruiterName['text']) ? 'selected' : ''}}>{{$recruiterName['text']}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    @if(LD::checkFeatureFlag('add-new-form-filed-in-therapy-neo-form'))
                        <div class="col-md-6">
                            <label for="work_location">Notes</label>
                            <input type="input" class="form-control" id="notes" name="notes" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->notes : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}>
                        </div>
                    @else
                        <div class="col-md-6">
                            <label for="work_location">Hours/Days</label>
                            <input type="input" class="form-control" id="hours_days" name="hours_days" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->hours_days : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}>
                        </div>
                    @endif
                    <div class="col-md-6">
                        <label for="attachment">Attachment</label>
                        <input type="hidden" id="attachment_hidden" name="attachment_hidden" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->attachment : ''}}" />
                        <input type="file" class="form-control" id="attachment" name="attachment" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->attachment : ''}}">
                        <span id="error" style="color: red;"></span>
                    </div>
                </div>
                <div class="form-group row pt-2">
                    <div class="col-md-12">
                        <button class="btn btn-primary" id="addTherapyBtn" type="button">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>
<script>
    $('#addTherapyIpeaEvent').on('hidden.bs.modal', function(e) {
        if ($('body.getSchedules #dataTable').length) {
            $('#dataTable').DataTable().ajax.reload();;
        }
    });
    $("#addTherapyBtn").click(function(event) {
        var vForm = $('#therapyEventForm');
        var formData = new FormData($('#therapyEventForm')[0]);
        var imageInput = $('#attachment')[0];
        var imageFile = imageInput.files[0];
        if (imageFile) {
            formData.append('attachment', imageFile);
        }
        event.preventDefault();
        if (vForm[0].checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
        } else {

            $('#addTherapyBtn').attr("disabled", true);
            $('#addTherapyBtn').text('Please wait...');
            $.ajax({
                url: "{{ route('schedules.savetherapyipeaevent') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {

                    $('#addTherapyBtn').removeAttr("disabled");
                    $('#addTherapyBtn').text('Save');
                    if (response) {

                        if (response.success == 1) {
                            $("#successBody").html(response.message);
                            $("#therapyEventFormDiv").hide();
                            $("#therapyEventSuccessDiv").show();
                            //$("#scheduleNextForm").modal('show');
                            //$("#report_issue_form")[0].reset();
                            @if($mode == 'reschedule')
                            getWeekEvents($('#states').val(), $('#branchSelect').val(), $("#date").val(), $("#ipeaCalendarSearch").attr('data-value'), function(data) {});
                            @endif
                        } else {
                            $("#error").text(response.message);
                            $("#therapyEventFormDiv").show();
                            $("#therapyEventSuccessDiv").hide();
                        }

                    }
                }
            });
        }
        vForm.addClass('was-validated');
    });
    $('#appointment_time').timepicker({
        "step": 15,
        'forceRoundTime': true,
        'minTime': '08:00:00',
        'maxTime': '19:00:00'
    });
</script>
