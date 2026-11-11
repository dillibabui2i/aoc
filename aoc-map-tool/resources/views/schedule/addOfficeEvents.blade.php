<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">

<x-modal>
    <x-slot name="id">addOfficeIpeaEvent</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">{{ ($mode == 'edit' || $mode == 'reschedule') ? 'Edit Office NEO' : 'Add Office NEO' }}</x-slot>

    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div id="officeEventSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" data-dismiss="modal" aria-label="Close" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div id="officeEventFormDiv">
            <form action="{{route('schedules.saveofficeipeaevent')}}" method="POST" id="addOfficeEventsForm">
                @csrf
                <input type="hidden" name="type_id" value="997" />
                <input type="hidden" name="mode" id="mode" value="{{ $mode }}" />
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
                                <option {{((isset($lastCalendarSearch['branch']) && $b['id'] == $lastCalendarSearch['branch'])  || (($mode == 'edit' || $mode == 'reschedule') && $b['id'] == $data->ipea_location_id)) ? 'selected="selected"' : ''}} value="{{$b['id']}}">{{$b['branch']}}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please Select Branch. This field is required</div>
                    </div>


                    <div class="col-md-6">
                        <label for=""> NEO Date<abbr class="text-danger" title="This is required">*</abbr> </label>
                        <input type="date" required class="form-control" name="appointment_date" id="appointment_date" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->appointment_date : ''}}"/>
                        <div class="invalid-feedback">Please enter your Appointment Date. This field is required</div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for=""> NEO Time<abbr class="text-danger" title="This is required">*</abbr> </label>
                        <input type="text" required class="form-control" name="appointment_time" id="appointment_time" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->appointment_time : ''}}"/>
                        <div class="invalid-feedback">Please enter your Appointment Time. This field is required</div>
                    </div>

                    <div class="col-md-6">
                        <label for=""> First Name</label>
                        <input type="input" class="form-control" name="first_name" id="first_name" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->first_name : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}/>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for=""> Last Name </label>
                        <input type="text" class="form-control" name="last_name" id="last_name" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->last_name : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}/>
                    </div>

                    <div class="col-md-6">
                        <label for="phone">Phone No</label>
                        <input type="input" class="form-control" id="phone" name="phone" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->phone : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}/>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="email">Email</label>
                        <input type="input" class="form-control" id="email" name="email" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->email : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}/>
                    </div>

                    <div class="col-md-6">
                        <label for="fcra_form_completed">FCRA Form completed?</label>
                        <select id="fcra_form_completed" name="fcra_form_completed" class="form-control" autocomplete="off" {{($mode == 'reschedule') ? 'readonly' : ''}}>
                            <option value="">-- Select One --</option>
                            <option value="Yes" {{(($mode == 'edit' || $mode == 'reschedule') && $data->fcra_form_completed == 'Yes') ? 'selected' : ''}}>Yes</option>
                            <option value="No" {{(($mode == 'edit' || $mode == 'reschedule') && $data->fcra_form_completed == 'No') ? 'selected' : ''}}>No</option>
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="background_check_completed" style="width: 65%;">Background Check Completed?</label>
                        <select id="background_check_completed" name="background_check_completed" class="form-control" autocomplete="off" {{($mode == 'reschedule') ? 'readonly' : ''}}>
                            <option value="">-- Select One --</option>
                            <option value="Yes" {{(($mode == 'edit' || $mode == 'reschedule') && $data->background_check_completed == 'Yes') ? 'selected' : ''}}>Yes</option>
                            <option value="No" {{(($mode == 'edit' || $mode == 'reschedule') && $data->background_check_completed == 'Yes') ? 'selected' : ''}}>No</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="onboarding_packet">Onboarding Packet</label>
                        <input type="input" class="form-control" id="onboarding_packet" name="onboarding_packet" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->onboarding_packet : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}/>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="position">Position</label>
                        <input type="input" class="form-control" id="position" name="position" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->position : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}/>
                    </div>

                    <div class="col-md-6">
                        <label for="work_location">Work Location</label>
                        <input type="input" class="form-control" id="work_location" name="work_location" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->work_location : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}/>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="supervisor">Supervisor</label>
                        <input type="input" class="form-control" id="supervisor" name="supervisor" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->supervisor : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}/>
                    </div>

                    <div class="col-md-6">
                        <label for="work_location">Full Time/Part Time</label>
                        <input type="input" class="form-control" id="full_part_time" name="full_part_time" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->full_part_time : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}/>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="benefits_class">Benefits Class</label>
                        <input type="input" class="form-control" id="benefits_class" name="benefits_class" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->benefits_class : ''}}" {{($mode == 'reschedule') ? 'readonly' : ''}}/>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check" style="padding-top: 13%;">
                            <input class="form-check-input" type="checkbox" value="1" id="clinical_license" name="clinical_license" {{(($mode == 'edit' || $mode == 'reschedule') && $data->clinical_license) ? 'checked' : ''}} {{($mode == 'reschedule') ? 'disabled' : ''}}>
                            <label class="form-check-label" for="clinical_license">
                                Clinical License
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="attachment">Attachment</label>
                        <input type="hidden" id="attachment_hidden" name="attachment_hidden" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->attachment : ''}}" />
                        <input type="file" class="form-control" id="attachment" name="attachment" value="{{($mode == 'edit' || $mode == 'reschedule') ? $data->attachment : ''}}">
                        <span id="error" style="color: red;"></span>
                    </div>
                </div>
                <div class="form-group row discipline-container" style="{{(($mode == 'edit' || $mode == 'reschedule') && $data->clinical_license) ? '' : 'display: none;'}}">
                    <div class="col-md-6">
                        <label for="discipline">Discipline</label>
                        <select id="discipline" name="discipline" class="form-control" autocomplete="off" {{($mode == 'reschedule') ? 'readonly' : ''}}>
                            <option value="">-- Select One --</option>
                            <option value="LVN/LPN" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'LVN/LPN') ? 'selected' : ''}}>LVN/LPN</option>
                            <option value="RN" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'RN') ? 'selected' : ''}}>RN</option>
                            <option value="PT" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'PT') ? 'selected' : ''}}>PT</option>
                            <option value="PTA" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'PTA') ? 'selected' : ''}}>PTA</option>
                            <option value="OT" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'OT') ? 'selected' : ''}}>OT</option>
                            <option value="OTA" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'OTA') ? 'selected' : ''}}>OTA</option>
                            <option value="ST" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'ST') ? 'selected' : ''}}>ST</option>
                            <option value="STA" {{(($mode == 'edit' || $mode == 'reschedule') && $data->discipline == 'STA') ? 'selected' : ''}}>STA</option>
                        </select>
                    </div>
                </div>
                <div class="form-group row pt-2">
                    <div class="col-md-12">
                        <button class="btn btn-primary" id="addOfficeEventsBtn" type="button">Save</button>
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
    $('#addOfficeIpeaEvent').on('hidden.bs.modal', function (e) {
        if ($('body.getSchedules #dataTable').length) {
            $('#dataTable').DataTable().ajax.reload();;
        }
    });
    $("#addOfficeEventsBtn").click(function(event) {
        var vForm = $('#addOfficeEventsForm');
        var formData = new FormData($('#addOfficeEventsForm')[0]);
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

            $('#addOfficeEventsBtn').attr("disabled", true);
            $('#addOfficeEventsBtn').text('Please wait...');
            $.ajax({
                url: "{{ route('schedules.saveofficeipeaevent') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {

                    $('#addOfficeEventsBtn').removeAttr("disabled");
                    $('#addOfficeEventsBtn').text('Save');
                    if (response) {

                        if (response.success == 1) {
                            $("#successBody").html(response.message);
                            $("#officeEventFormDiv").hide();
                            $("#officeEventSuccessDiv").show();
                            //$("#scheduleNextForm").modal('show');
                            //$("#report_issue_form")[0].reset();
                            @if($mode == 'reschedule')
                            getWeekEvents($('#states').val(), $('#branchSelect').val(), $("#date").val(), $("#ipeaCalendarSearch").attr('data-value'),function(data) {});
                            @endif
                        } else {
                            $("#error").text(response.message);
                            $("#officeEventFormDiv").show();
                            $("#officeEventSuccessDiv").hide();
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
    $('#clinical_license').click(function(){
        $('.discipline-container').toggle();
    });
</script>
