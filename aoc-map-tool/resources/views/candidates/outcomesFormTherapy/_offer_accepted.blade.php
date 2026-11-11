@php
    use App\Helpers\LD;
    use App\Helpers\H;
@endphp
<style>
    input[type="date"]:before {
        content: none !important;
    }
</style>
<div class="col-lg-12 offer-accepted-container">
    <div class="row pt-3">
        <div class="col-md-12 p-0 mt-0 tabs-container">

            <div class="card mr-5 no-border p-0">
                <div class="form-group row">
                    <div class="col-md-3">
                        <label for="datepicker">Signed Date</label>
                        <input type="text" class="form-control required-field" id="datepicker" autocomplete="off"
                               name="start_date" value=""/>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Offer Accepted notes</label>
                        <textarea name="note" class="form-control" rows="5"></textarea>
                    </div>
                    <div class="col-md-8 mt-3">
                        <label for="offer_accepted_file">PDF of the offer accepted</label>
                        <input type="file" class="form-control required-field mb-2" id="offer_accepted_file" name="offer_accepted_file" value=""/>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-12 mt-3">
                        <input type="checkbox" id="show_neo_form" name="neo_form">
                        <label for="show_neo_form">I want to fill out Therapy NEO form</label>
                    </div>
                </div>
            </div>


            <div id="add-therapy-neo-container" class="hide">
                <input type="hidden" name="type_id" value="998"/>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for=""> NEO Location <abbr class="text-danger" title="This is required">*</abbr> </label>
                        <select class="form-control" name="ipea_location_id" id="ipea_location_id">
                            <option value=""> Select Branch</option>
                            @foreach($branches as $key => $branch)
                                <optgroup label="{{$key}}">
                                    @foreach($branch as $b)
                                        <option value="{{ $b['id'] }}">{{$b['branch']}}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please Select Branch. This field is required</div>
                    </div>


                    <div class="col-md-6">
                        <label for=""> NEO Date<abbr class="text-danger" title="This is required">*</abbr> </label>
                        <input type="date" class="form-control" name="appointment_date" id="appointment_date" value=""/>
                        <div class="invalid-feedback">Please enter your Appointment Date. This field is required</div>
                    </div>
                </div>
                @if(LD::checkFeatureFlag('add-new-form-filed-in-therapy-neo-form'))
                    <div class="form-group row">
                        <div class="col-md-6 bilingualclass">
                            <div class="custom-control custom-switch custom-control-right">
                                <input type="hidden" name="bilingual" value="0"/>
                                <input class="custom-control-input" type="checkbox" id="bilingual" name="bilingual"
                                       value="1"/>
                                <label class="custom-control-label" for="bilingual">Bilingual?</label>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for=""> NEO Time<abbr class="text-danger" title="This is required">*</abbr> </label>
                        <input type="text" class="form-control" name="appointment_time" id="appointment_time" value=""/>
                        <div class="invalid-feedback">Please enter your Appointment Time. This field is required</div>
                    </div>

                    <div class="col-md-6">
                        <label for=""> First Name</label>
                        <input type="input" class="form-control" name="first_name" id="first_name"
                               value="{{ $candidatesDetails->firstName }}"/>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for=""> Last Name </label>
                        <input type="text" class="form-control" name="last_name" id="last_name"
                               value="{{ $candidatesDetails->lastName }}"/>
                    </div>
                    <div class="col-md-6">
                        <label for="discipline">Discipline</label>
                        <select id="discipline" name="discipline" class="form-control" autocomplete="off">
                            <option value="">-- Select One --</option>
                            <option value="BCBA">BCBA</option>
                            <option value="BT">BT</option>
                            <option value="OT">OT</option>
                            <option value="OTA">OTA</option>
                            <option value="PT">PT</option>
                            <option value="PTA">PTA</option>
                            <option value="RBT">RBT</option>
                            <option value="ST">ST</option>
                            <option value="STA">STA</option>
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="phone">Phone No</label>
                        <input type="input" class="form-control" id="phone" name="phone"
                               value="{{ $candidatesDetails->mobile }}">
                    </div>
                    <div class="col-md-6">
                        <label for="email">Email</label>
                        <input type="input" class="form-control" id="email" name="email"
                               value="{{ $candidatesDetails->email }}">
                    </div>

                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="zipcode">Zip Code</label>
                        <input type="input" class="form-control" id="zipcode" name="zipcode"
                               value="{{ $candidatesDetails->postalCode }}">
                    </div>


                    <div class="col-md-6">
                        <label for="recruiter">Recruiter </label>
                        <select id="recruiter" name="recruiter" class="form-control" autocomplete="off">
                            <option value="">-- Select One --</option>
                            @php
                                $therapyRecruiters = H::getHelper("ConstantHelper")->getUsersByRoles(['Therapy Recruiter']);
                            @endphp
                            @foreach($therapyRecruiters as $recruiterName)
                                <option value="{{$recruiterName['text']}}">{{$recruiterName['text']}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    @if(LD::checkFeatureFlag('add-new-form-filed-in-therapy-neo-form'))
                        <div class="col-md-6">
                            <label for="work_location">Notes</label>
                            <input type="text" class="form-control" id="notes" name="notes" value="">
                        </div>
                    @else
                        <div class="col-md-6">
                            <label for="work_location">Hours/Days</label>
                            <input type="input" class="form-control" id="hours_days" name="hours_days" value="">
                        </div>
                    @endif
                    <div class="col-md-6">
                        <label for="attachment">Attachment</label>
                        <input type="hidden" id="attachment_hidden" name="attachment_hidden" value=""/>
                        <input type="file" class="form-control" id="attachment" name="attachment" value="">
                        <span id="error" style="color: red;"></span>
                    </div>
                </div>
                <div class="form-group row pt-2">
                    <div class="col-md-12">
                        <button class="btn btn-primary" id="addTherapyBtn" type="button">Save</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<script>
    validation = {
        ignore: ":hidden",
        onfocusout: false,
        invalidHandler: function (form, validator) {
            if (validator.numberOfInvalids()) {
                validator.errorList[0].element.focus();
            }
        },
        errorPlacement: function (error, element) {
        },
        rules: {
            start_date: {
                required: true
            },
            visits: {
                required: true
            },
            ipea_location_id: {
                required: {
                    depends: function () {
                        return $('#show_neo_form').is(':checked');
                    }
                }
            },
            appointment_date: {
                required: {
                    depends: function () {
                        return $('#show_neo_form').is(':checked');
                    }
                }
            },
            appointment_time: {
                required: {
                    depends: function () {
                        return $('#show_neo_form').is(':checked');
                    }
                }
            },
            first_name: {
                required: {
                    depends: function () {
                        return $('#show_neo_form').is(':checked');
                    }
                }
            },
            last_name: {
                required: {
                    depends: function () {
                        return $('#show_neo_form').is(':checked');
                    }
                }
            },
            phone: {
                required: {
                    depends: function () {
                        return $('#show_neo_form').is(':checked');
                    }
                }
            },
            email: {
                required: {
                    depends: function () {
                        return $('#show_neo_form').is(':checked');
                    }
                },
                email: {
                    depends: function () {
                        return $('#show_neo_form').is(':checked');
                    }
                }
            }
        }
    };

    $('#show_neo_form').off('change.offerAccepted').on('change.offerAccepted', function () {
        $('#add-therapy-neo-container').toggleClass('hide', !$(this).is(':checked'));

        var validator = $('#jobViteOutcomeForm').data('validator');
        if (validator) {
            validator.resetForm();
        }
    });

    $('#datepicker').datepicker({
        onSelect: function () {
            $(this).valid();
        }
    }).on('change', function () {
        $(this).valid();
    });

    $('#datepicker').on('click', function () {
        $('#ui-datepicker-div').appendTo($(this).parent());
    });

    $('#appointment_time').timepicker({
        step: 15,
        forceRoundTime: true,
        minTime: '08:00:00',
        maxTime: '19:00:00'
    });
</script>
