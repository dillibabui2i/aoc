<?php

use App\Http\Controllers\CandidatesController;
use App\Helpers\LD;
use App\Helpers\H;

/**
 * @see CandidatesController::getAddCandidateForm
 */
?>

<style>

  #add_candidate .multiselect-native-select .btn-group {
    max-width: 100% !important;
    width: 100% !important;
  }

  #add_candidate span.multiselect-native-select {
    position: relative;
    width: 100%;
  }
</style>

<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<x-modal>
    <x-slot name="id"> add_candidate</x-slot>
    <x-slot name="title">Candidate Information</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="modal-body" id="addCandidateSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" onclick="closeForm()" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div class="modal-body" id="addCandidateFormDiv">
            <form action="{{route('schedules.savetherapyipeaevent')}}" method="POST" id="add_new_candidate">
                @csrf
                <input type="hidden" name="jobCategoryType" value="{{ $jobCategoryType }}">
                <div class="alert alert-danger" style="display:none"></div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="firstName"> First Name</label>
                        <input type="input" class="form-control" name="firstName" id="firstName" />
                        <span class="text-danger" id="firstNameError"></span>
                    </div>
                    <div class="col-md-6">
                        <label for="lastName"> Last Name </label>
                        <input type="text" class="form-control" name="lastName" id="lastName" />
                        <span class="text-danger" id="lastNameError"></span>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="mobile">Phone No</label>
                        <input type="input" class="form-control" id="mobile" name="mobile">
                    </div>
                    <div class="col-md-6">
                        <label for="email">Email</label>
                        <input type="input" class="form-control" id="email" name="email">
                        <span class="text-danger" id="emailError"></span>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="title">Title </label>
                        <select id="title" name="title" class="form-control" autocomplete="off">
                        <option value="">-- Select One --</option>
                            @include('candidates.partials.titles', ['jobCategoryType' => $jobCategoryType])
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="">Graduation Date</label>
                        <input type="text" placeholder="mm/dd/yyyy" class="form-control" name="GraduationDate" id="GraduationDate" autocomplete="off" />
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="address">Address</label>
                        <input type="input" class="form-control" id="address" name="address">
                    </div>
                    <div class="col-md-6">
                        <label for="city">City</label>
                        <input type="input" class="form-control" id="city" name="city">
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="state">State</label>
                        <select name="state" id="state" class="form-control">
                            <option value="">-- Select One --</option>
                            @foreach(config('constant')['states'] as $k => $state)
                            <option {{isset($candidatesDetails->state) && $candidatesDetails->state == $k ? "selected='selected'" : ""}}  value="{{$k}}">{{$state}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="postalCode">Zip Code</label>
                        <input type="input" class="form-control" id="postalCode" name="postalCode">
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="how_hear_about_us" style="width: 65%;">How did you hear about us? </label>
                        <select id="how_hear_about_us" name="how_hear_about_us" class="form-control" autocomplete="off">
                            <option value="">Select One</option>
                            <option value="Career Fair">Career Fair</option>
                            <option value="Friend">Friend</option>
                            <option value="Facebook">Facebook</option>
                            <option value="Google Web Search">Google Web Search</option>
                            <option value="Indeed">Indeed</option>
                            <option value="Instagram">Instagram</option>
                            <option value="LinkedIn">LinkedIn</option>
                            <option value="Other">Other</option>
                            <option value="Postcard">Postcard</option>
                            <option value="Twitter">Twitter</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="recruiter" style="width: 65%;"> Assign to Recruiter</label>
                        <select class="form-control" name="recruiter" id="recruiter">
                            <option value="">Select Recruiter</option>
                            @foreach($recruiters as $k => $v)
                            <option value="{{$v['value']}}" <?php if($recruiterEmail==$v['value']) { ?> selected="selected" <?php }?>>{{$v['text']}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row pt-1">
                    <div class="col-md-12">
                        <label for="comments">Comments</label>
                        <textarea class="form-control" id="comments" name="comments"  rows="3"></textarea>
                    </div>
                </div>
                <div class="row pt-3">
                    <div class="col-md-12 text-center">
                        <h4 class="font-weight-bold">Application Source</h4>
                        <hr />
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="source_type">Source Type</label>
                        <select id="source_type" name="source_type" class="form-control">
                        <option value="">Select Source Type</option>
                            @foreach($appSourceType as $appSourceVal)
                                <option value="{{$appSourceVal}}">{{$appSourceVal}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="source_sub_type">Source</label>
                        <select id="source_sub_type" name="source" class="form-control">
                        </select>
                    </div>
                </div>
                <div class="row pt-3">
                    <div class="col-md-12 text-center">
                        <h4  class="font-weight-bold">Select Requisition</h4>
                        <hr />
                    </div>
                </div>
                <div id="jobMain">
                <div class="form-group row job-row" id="jobDiv1">
                    <div class="col-md-6">
                        <label for="category1">Category</label>
                        <select class="form-control job_category" name="category[]" id="category1" data-count="1">
                            <option value="">Select Category</option>
                            @foreach($categoryList as $key => $value)
                            <option value="{{$value}}">{{$value}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="job_id1">Requisition</label>
                        {{-- Requisitions can only be picked from this list, never typed in --}}
                        <select class="form-control job_requisition" name="job_id[]" id="job_id1" data-count="1">
                            <option value="">Select Category first</option>
                        </select>
                    </div>
                </div>
                </div>
                <div class="form-group row" id="addMoreJobDiv">
                    <div class="col-md-6">
                        <a href="javascript:void(0)" id="addMoreJobLink">Add More</a>
                    </div>
                </div>

                @if($jobCategoryType !== 'therapy' || is_null($jobCategoryType))
                    @if(LD::checkFeatureFlag('add-custom-field-to-candidate'))
                        @include('candidates.customFields', ['customFields' => $customFields])
                    @endif
                @endif

                <div class="form-group row pt-2">
                    <div class="col-md-6 text-right">
                        <button class="btn btn-primary" onclick="setSchedule(this);" type="button">Add Candidate</button>
                    </div>
                    @if($jobCategoryType !== 'therapy' || is_null($jobCategoryType))
                        <div class="col-md-6 text-left">
                            <button class="btn btn-primary" onclick="setSchedule(this,1);" type="button">Add Candidate & Schedule</button>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>

<x-modal>
    <x-slot name="id">candidateCheck</x-slot>
    <x-slot name="title">Warning</x-slot>
    <x-slot name="body">
        <div class="col-md-12">
            <p>Candidate cannot be added — previously marked as Do Not Contact/Do Not Hire.</p>
            <button type="button" data-bs-dismiss="modal" id="close-candidateCheck" class="btn btn-light ">Close</button>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script type="text/javascript">

  // Row ids only ever grow, so removing a row can never produce a duplicate id.
  var nextJobRowId = 2;
  var MAX_REQUISITION_ROWS = 3;
  var sourceType = <?php echo json_encode($appSourceTypeData); ?>;
  // Wait for the DOM to be ready
  $(function () {

    let requiredCustomFields = @json(
        $customFields
            ->where('is_required', true)
            ->pluck('db_field_name')
    );

    const rules = {
      "firstName": { required: true },
      "lastName": { required: true },
      "title": { required: true },
      "email": { required: true },
      "recruiter": { required: true },
      "source_type": { required: true },
      "category[]": { required: true },
    };

    requiredCustomFields.forEach(function (field) {
      rules[`custom_fields[${field}]`] = { required: true };
    });

    $("#add_new_candidate").validate({
      onfocusout: false,
      invalidHandler: function (form, validator) {
        var errors = validator.numberOfInvalids();
        if (errors) {
          validator.errorList[0].element.focus();
        }
      },
      rules: rules,
      errorPlacement: function (error, element) {}
    });
  });
  function setRequisitionOptions(count, list, placeholder) {
    var $select = $('#job_id' + count).empty();

    $select.append($('<option/>', { value: '', text: placeholder }));

    $.each(list, function (index, item) {
      $select.append($('<option/>', { value: item.id, text: item.label }));
    });
  }

  function loadRequisitions(count) {
    var category = $('#category' + count).val();

    if (!category) {
      setRequisitionOptions(count, [], 'Select Category first');
      return;
    }

    setRequisitionOptions(count, [], 'Loading...');

    $.getJSON("{{ route('getJobListByCategory') }}", { category: category, term: '' })
      .done(function (list) {
        setRequisitionOptions(
          count,
          list,
          list.length ? 'Select Requisition' : 'No open requisitions for this category'
        );
      })
      .fail(function () {
        setRequisitionOptions(count, [], 'Could not load requisitions, please try again');
      });
  }

  function toggleAddMoreLink() {
    $('#addMoreJobDiv').toggle($('#jobMain .job-row').length < MAX_REQUISITION_ROWS);
  }

  function resetRequisitionRows() {
    $('#jobMain .job-row').not('#jobDiv1').remove();
    toggleAddMoreLink();
    $('#category1').val('');
    setRequisitionOptions(1, [], 'Select Category first');
  }

  $(document).ready(function () {
    $("#addMoreJobLink").on('click', function () {
      var count = nextJobRowId++;
      var selectOptionHtml = $("#category1").html();
      var newHtml = '<div class="form-group row job-row" id="jobDiv' + count + '"><div class="col-md-6"><label for="category' + count + '">Category</label><select class="form-control job_category" name="category[]" id="category' + count + '" data-count="' + count + '">' + selectOptionHtml + '</select></div><div class="col-md-6"><label for="job_id' + count + '">Requisition</label><select class="form-control job_requisition" name="job_id[]" id="job_id' + count + '" data-count="' + count + '"><option value="">Select Category first</option></select><a href="javascript:void(0)" class="removeMoreJobLink" onclick=removeMoreDiv("' + count + '")>Remove</a></div></div>';
      $('#jobMain').append(newHtml);
      toggleAddMoreLink();
    });

    $(document)
      .off('change.addCandidateRequisition')
      .on('change.addCandidateRequisition', 'select.job_category', function () {
        loadRequisitions($(this).data('count'));
      });
    $('#GraduationDate').datepicker({
      dateFormat: 'mm/dd/yy',
      beforeShow: function(input, inst) {
        setTimeout(function() {
          var rect = input.getBoundingClientRect();
          inst.dpDiv.css({
            position: 'fixed',
            top: rect.bottom,
            left: rect.left,
            'z-index': 99999
          });
        }, 0);
      }
    });
    $('#source_type').on('change', function () {
      let thisArry = sourceType[this.value];
      let options = '';
      if (this.value != '') {
        for (var i = 0, l = thisArry.length; i < l; i++) {
          options += '<option value="' + thisArry[i] + '">' + thisArry[i] + '</option>';
        }
      }
      $("#source_sub_type").html(options);
    });

    $('.datepicker').datepicker({
      dateFormat: 'mm/dd/yy'
    }).on('click', function () {
      $('#ui-datepicker-div').appendTo($(this).parent());
    });
  });

  function removeMoreDiv(div_id) {
    $('#jobDiv' + div_id).remove();
    toggleAddMoreLink();
  }

  function closeForm() {
    $('#add_candidate').modal('hide');
    location.reload();
  }

  $('#close-candidateCheck').on('click', function (e) {
      $('#candidateCheck').modal('hide');
  })

  function setSchedule(button, saveandnew = 0) {
      if (!$("#add_new_candidate").valid()) {
          return false;
      }

      // Checked here rather than through jQuery Validate, whose errorPlacement is a no-op
      // on this form. StoreCandidateRequest re-checks every id against the jobs table.
      var selectedRequisitions = [];
      var missingRequisition = false;

      $('select.job_requisition').each(function () {
          var value = $(this).val();

          if (!value) {
              missingRequisition = true;
              return;
          }

          selectedRequisitions.push(value);
      });

      if (missingRequisition || !selectedRequisitions.length) {
          $('.alert-danger').html('<p>Please select a requisition from the list for every category.</p>').show();
          return false;
      }

      if (new Set(selectedRequisitions).size !== selectedRequisitions.length) {
          $('.alert-danger').html('<p>The same requisition is selected more than once.</p>').show();
          return false;
      }

      const $city = $('#aoc_cf_city');
      if ($city.data('pureCity')) {
          $city.val($city.data('pureCity'));
      }

      button.disabled = true;

      $.ajax({
          url: "{{ route('saveNewCandidate') }}",
          type: "POST",
          data: $("#add_new_candidate").serialize(), //only input
          success: function (response) {

              if(response['modal']) {
                  let r = false;
                  if(!response['success']) {
                      $('#candidateCheck').modal('show');
                      button.disabled = false;
                      return false;
                  }
              }

              button.disabled = false;
              console.log(response);
              if (response) {
                  $("#successBody").html(response.message);
                  if (response.success == 1) {
                      if (saveandnew == 0) {
                          $("#addCandidateFormDiv").hide();
                          $("#addCandidateSuccessDiv").show();
                          //$("#addCandidateForm").modal('show');
                          $("#add_new_candidate")[0].reset();
                          resetRequisitionRows();
                      } else {
                          $("#addCandidateFormDiv").hide();
                          $("#add_new_candidate")[0].reset();
                          resetRequisitionRows();
                          $('#add_candidate').modal('hide');
                          showScheduleForm('', 'candidates##' + response.candidate_id, '');

                      }
                  } else {
                      $('#firstName').focus();
                      $('.alert-danger').html('');
                      $.each(response.error, function (key, value) {
                          $('.alert-danger').show();
                          $('.alert-danger').append('<p>' + value + '</p>');
                      });
                      $("#addCandidateFormDiv").show();
                      $("#addCandidateSuccessDiv").hide();
                  }

              }
          },
          error: function (xhr) {
              button.disabled = false;
              $('.alert-danger').html('');
              const errors = xhr.responseJSON && xhr.responseJSON.errors;
              if (errors) {
                  $.each(errors, function (key, messages) {
                      $('.alert-danger').append('<p>' + messages[0] + '</p>');
                  });
              } else {
                  $('.alert-danger').append('<p>Something went wrong, please try again.</p>');
              }
              $('.alert-danger').show();
          },
      });
  }

  $(document).ready(function () {
    $('#add_candidate').on('shown.bs.modal', function () {
      $("#addCandidateFormDiv").show();
      $("#addCandidateSuccessDiv").hide();
    });
  });

  $(document).ready(function () {
    const $city = $('#aoc_cf_city');
    const $state = $('#aoc_cf_state');

    const canEdit = @json(H::canPermissions('editCustomFieldValue'));

    if (!canEdit) {
      $('.editable-field').prop('disabled', true);
      $('.multiselect-editable').multiselect('disable');
      return;
    }

    $city.autocomplete({
      minLength: 3,
      source: function (request, response) {
        $.ajax({
          url: "{{ route('nthlanding.getGeoCities') }}",
          type: 'GET',
          dataType: 'json',
          data: {
            _token: '{{ csrf_token() }}',
            term: request.term
          },
          success: function (data) {
            response($.map(data, function (item) {
              let parts = item.label.split(',');
              let city = parts[0].trim();
              let state = parts[1] ? parts[1].trim() : '';

              return {
                label: `${city}${state ? ', ' + state : ''}`,
                city: city,
                state: state
              };
            }));
          }
        });
      },
      focus: function (event, ui) {
        $city.val(`${ui.item.city}${ui.item.state ? ', ' + ui.item.state : ''}`);
        return false;
      },
      select: function (event, ui) {
        $city.val(`${ui.item.city}${ui.item.state ? ', ' + ui.item.state : ''}`);
        $city.data('pureCity', ui.item.city);
        $state.val(ui.item.state);
        return false;
      }
    })
  });

  $(document).ready(function () {
    // Bootstrap Multiselect
    $('.multiselect-editable').multiselect({
      includeSelectAllOption: true,
      enableFiltering: true,
      nonSelectedText: 'Select options',
      selectAllText: 'Select All',
      nSelectedText: 'selected',
      allSelectedText: 'All selected',
      buttonWidth: '100%',
      numberDisplayed: 100,
      buttonContainer: '<div class="btn-group w-100" />',
      buttonText: function (options, select) {
        if (options.length === 0) {
          return 'Select options';
        } else {
          return options.map(function () {
            return $(this).text();
          }).get().join(', ');
        }
      }
    });

    $('.multiselect-editable').each(function () {
      const $btn = $(this).next('.btn-group').find('button.multiselect');
      $btn.css({
        'height': 'auto',
        'min-height': '40px',
        'white-space': 'normal',
        'line-height': '1.5',
        'overflow': 'visible',
      });
    });
  });

</script>
