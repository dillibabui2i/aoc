@extends('layouts.nthLandingLayout')
@section('title', 'NTH - Available Cases')
@section('content')
<?php
use App\Helpers\H;
//var_dump($clinicianData);
//exit;
$schedule = [
    ['value' => 'Weekday Days (AM-PM)', 'text' => 'Weekday Days (AM-PM)'],
    ['value' => 'Weekday Evenings (PM-PM)', 'text' => 'Weekday Evenings (PM-PM)'],
    ['value' => 'Weekday Nights (PM-AM)', 'text' => 'Weekday Nights (PM-AM)'],
    ['value' => 'Weekend Days (AM-PM)', 'text' => 'Weekend Days (AM-PM)'],
    ['value' => 'Weekend Evenings (PM-PM)', 'text' => 'Weekend Evenings (PM-PM)'],
    ['value' => 'Weekend Nights (PM-AM)', 'text' => 'Weekend Nights (PM-AM)']
];

?>
<div class="container mt-5 pr-0 pl-0">
	<div class="f">
    <div class="text-center mb-4 row name">
      <h2>Welcome, {{$clinicianData->FirstName}}!</h2>

    </div>

    <div class="filter-nav row">
<!--
        <div class="col-md-9">
      <div class="filter-tag row pt-3 ">
        <div class="tags col-md-10 pull-left"  id="filter-tag">
        </div>
    </div>
        </div>
-->

        <div class="col-md-3 text-right">
          <button type="button" name="filter-button" class="filter-button" id="filter-button">Filter</button>
          &nbsp;
          <button type="button" name="orderby-button" class="orderby-button" id="orderby-button">Sort By: {{$clinicianData->aoc_cf_default_order}}</button>
        </div>
    </div>
</div>
    <div class="result-filter row pt-5" id="result-filter" style="display:none">
      <div class="filter-row row g-3">
        <form name="form_result_filter" id="form_result_filter">
			<span class="c-all"><a href="javascript:void(0)" onclick="clearAll()">Clear All</a></span>
            <input type="hidden" name="orderby" id="orderby" value="{{$clinicianData->aoc_cf_default_order}}" />
            <input type="hidden" name="clearall" id="clearall" value="0" />

            <div class="city-filter col-md-4 pull-left">
              <label> Your Location </label>
              <input type="text" class="form-control" placeholder="City, State" name="city" id="city" value="{{ old('city', $clinicianData->aoc_cf_city.', '.$clinicianData->aoc_cf_state ?? '') }}">
				<div class="desc">City and state where you want to find cases.</div>
            </div>

            <div class="radius-filter col-md-4 pull-left">

              <div class="desktop-element">
                <label> Search Radius (miles) </label>
                <select name="mile_radius" class="form-control desktop-control" id="mile_radius" style="max-width:100%">
                <?php for($i=15;$i<=75;$i+=15) { ?>
                    <option value="<?php echo $i?>" <?php if($clinicianData->aoc_cf_mile_radius==$i) { ?> selected="selected" <?php }?> ><?php echo $i?> Miles</option>
                <?php } ?>
            </select>
				  <div class="desc">How far are you willing to travel?</div>
                @error('mile_radius')
                      <div class="old-error">{{ $message }}</div>
                @enderror
              </div>
              <div class="mobile-element">
                <label>Search Radius (miles)</label>
                <select name="mile_radius" class="form-control mobile-control" id="mile_radius" style="max-width:100%">
                    <?php for($i=15;$i<=75;$i+=15) { ?>
                        <option value="<?php echo $i?>" <?php if($clinicianData->aoc_cf_mile_radius==$i) { ?> selected="selected" <?php }?> ><?php echo $i?> Miles</option>
                    <?php } ?>
                </select>
                <div class="desc">How far are you willing to travel?</div>
              </div>
            </div>

            <div class="schedule-filter  col-md-4 pull-left">
               <label> Preferred Schedule </label>
               <div class="">
                <select multiple class="selectpicker " data-placeholder="Select Schedule" data-live-search="true" name="schedule" id="schedule">
                  @foreach($schedule as $option)
                    <option value="{{$option['value']}}" {{ in_array($option['value'], old('schedule', explode(',', $clinicianData->aoc_cf_schedule ?? ''))) ? 'selected' : '' }}>{{$option['text']}}</option>
                  @endforeach
                </select>
				         <div class="desc">Days or times you are willing to work.</div>
              </div/>


            </div>

        </form>
      </div>
      <div class="col-md-12 buttons">
         <div class="col-md-5 pull-left text-right">
            <button type="button" name="apply-button" class="col-md-12 apply-button" id="apply-button" onclick="filterData()">Apply Filter</button>
        </div>
        <div class="tags col-md-5  text-right pull-left">
          <button type="button" name="preference-button" class="col-md-12 preference-button" id="preference-button" onclick="savePreference()">Save Preferences</button>
        </div>
      </div>


    </div>

    <div class="alert alert-danger" id="error-msg" style="display:none">
    </div>
    <div class="alert alert-success" id="success-msg"  style="display:none">
    </div>


    <div class="row pt-4">
        <div class="col-md-9">
           <span class="welcome-title">Available Cases</span>
        </div>

        <div class="col-md-3 text-right">

        </div>
    </div>
    <div class="box-contents col-md-12  row pt-5 pr-0 pl-0">

          <ul class="box-container"  id="box-contents" >
          </ul>
    </div>

    <div class="load-more row hide">

    </div>
    <div id="cover-spin"></div>
    <div id="toast-container"></div>

</div>


@include('nthlanding.show_error_message')
<script>

    var page = 1;
    var loading = false;
    var isLoadingDisabled = false;


    function setInterest(button,nthId){
        button.prop('disabled', true);
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('nthlanding.saveNthCaseInterest')}}",
            type: 'post',
            data: {
                aoc_nthaccounts_id: nthId,
                _token: _token
            },
            success: function(data) {
                if(data.status) {
                    if (data.action === 'added') {
                        button.text('Interested');
                        button.addClass('btn-green');
                        button.data('interested', 1);
                    } else if (data.action === 'removed') {
                        button.text("I'm Interested");
                        button.removeClass('btn-green');
                        button.data('interested', 0);
                    }
                } else {
                    showError(data.message)
                }
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    }
    $(document).ready(function() {
      var btnUnInterest = null;

      $(document).on("click",".btn-confirm-yes",function(e){
        let nthId = $(btnUnInterest).attr('data-nth-id');
        setInterest(btnUnInterest,nthId);
      });
      $(document).on('click', '.interest-btn', function(e) {
            const button = $(this);
            let nthId = $(this).attr('data-nth-id');
            let interested = button.data('interested') === 1;
            if (interested) {
                btnUnInterest = button;
                $("#interestdialog").modal("show");
                return ;
            } else {
              setInterest(button,nthId);
            }
        });
    });

  $(document).ready(function(){
      loadBoxContent();
      var flag = false;
      $("#city").autocomplete({
          source: function(request, response) {
              $.getJSON("{{ route('nthlanding.getGeoCities') }}", {
                  term: $('#city').val()
              }, response);
          },
          minLength: 2,
          select: function(event, ui) {
              flag = true;
              $("#city").val(ui.item.value);
          }
      });
      $('#city').keyup(function() {
          flag = false;
          if ($(this).val() == '') {
            // $('#candidate_id').val('');
          }
      });
      $('#city').blur(function() {
          if (flag == false) {
              $(this).val('');
              //$('#candidate_id').val('');
          }
      });
  });

  $(".filter-button").on("click",function(){
    $(".result-filter").toggle();
  });

  $(".orderby-button").on("click",function(){
      $(this).text(function(i, text){
          var orderBy = $("#orderby").val();
          console.log(orderBy);
          $("#orderby").val(orderBy=='Nearest'?"Farthest":"Nearest");

          return text === "Sort By: "+$("#orderby").val() ? "Sort By: Farthest" : "Sort By: "+$("#orderby").val();
      })
      page = 1;
      isLoadingDisabled = false;
      loadBoxContent();
  });



  $(document).on("click",".clear-filter",function(){
      var field = $(this).attr("data-id");
      var val = $(this).attr("data-value");
      if(field!='city' && field!='mile_radius' ) {
          if(isMobile) {
              if(field=='schedule') {
                $('input[type="checkbox"][name="schedule"][value="' + val + '"]').prop('checked', false);
              }
          } else {
            var selectedOptions = $("#"+field+" :selected").toArray().map(item => item.value);
            selectedOptions = jQuery.grep(selectedOptions, function(value) {
              return value != val;
            });
            $("#"+field).val(selectedOptions);
            $("#"+field).multiselect("refresh");
          }


      } else {
          if(field=='mile_radius') {
            if(isMobile) {
              $('input[type="checkbox"][name="mile_radius"][value="' + val + '"]').prop('checked', false);
            }
          }
          $("#"+field).val('');
      }

      $("#clearall").val(0);
      page = 1;
      isLoadingDisabled = false;
      loadBoxContent();

  });

  $('.selectpicker').multiselect({
    includeSelectAllOption: true,
  });

  function clearAll(){
    $("#clearall").val(1);
    page = 1;
    isLoadingDisabled = false;
    $("#city").val("");
    $("#mile_radius").val("");

    var fields = ["schedule","skills","environment","language"];
    fields.forEach(function(item) {
        $("#"+item).multiselect("clearSelection");
        // do something with `item`
    });

    loadBoxContent();
  }

  function loadMore(){
    if (loading) return;
    loading = true;
    page++;
    loadBoxContent();
  }

  function filterData() {
    page = 1;
    isLoadingDisabled = false;
    $("#clearall").val(0);
    loadBoxContent();
  }

  function loadBoxContent(){
        let _token = $('meta[name="csrf-token"]').attr('content');
        console.log(isLoadingDisabled);
        if (isLoadingDisabled) return;
        $("#cover-spin").show();
        $.ajax({
            url: "{{ route('nthlanding.results') }}",
            type: "GET",
            data: {
                page: page,
                filters:$("#form_result_filter").serializeArray(),
                _token: _token
            },
            success: function(response) {
              //console.log(response);
              var res = JSON.stringify(response);
              loading = false;
              $("#cover-spin").hide();
              if(response.clearall) {
                $("#box-contents").empty().append("<span class='apply-filter-text'>"+response.message+"</span>");
                $("#filter-tag").empty();
                $("#result-filter").show();
                $(".load-more").hide();
                $("#preference-button").hide();
              } else {
                $("#preference-button").show();
                if(!response.count) {
                  $(".load-more").hide();
                  isLoadingDisabled = true;
                } else {
                  $(".load-more").show();
                }

                if(page==1 && !response.count ) {
                  showToast("No matching cases found for your filters.","info");
                }

                $("#filter-tag").html(response.filters);

                if(page==1) {
                  $("#box-contents").empty().append(response.records);
                } else {
                  $("#box-contents").append(response.records);
                }
              }
            },
        });
  }

   $(window).on('scroll', function () {
    if ($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
      loadMore();
    }
  });

  function savePreference(){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $("#cover-spin").show();


        $.ajax({
            url: "{{ route('nthlanding.savepreference') }}",
            type: "POST",
            data: {
                filters:$("#form_result_filter").serializeArray(),
                _token: _token
            },
            success: function(response) {
              if(response.error) {
                $(".result-filter").show();
                showToast(response.message,"danger");
              } else {
                showToast(response.message,"success");
              }
              $("#cover-spin").hide();
            },
        });
  }

  function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');

    // Choose icon based on type
    let iconClass = '';
    if (type === 'success') iconClass = 'fa-solid fa-circle-check';
    if (type === 'danger') iconClass = 'fa-solid fa-triangle-exclamation';
    if (type === 'info') iconClass = 'fa-solid fa-square-info';

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
      <i class="${iconClass}"></i>
       <span class="toast-message">${message}</span>
      <button class="close-btn" onclick="this.parentElement.remove()">
        <i class="fas fa-times"></i>
      </button>
    `;

    container.appendChild(toast);

    // Auto-remove after 3s
    setTimeout(() => {
      if (toast.parentElement) toast.remove();
    }, 300000);
  }


</script>


<div class="modal fade" id="interestdialog" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
      <div class="modal-content">
      <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Confirmation</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
          </button>
      </div>
      <div class="modal-body">
          Are you sure you want to remove your interest?
      </div>
      <div class="modal-footer">
          <button type="button" class="btn btn-primary btn-confirm-yes" data-dismiss="modal">Yes</button>
          <button type="button" class="btn btn-primary btn-confirm-no" data-dismiss="modal">No</button>
      </div>
      </div>
  </div>
</div>

<style>#toast-container {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 9999;
}

.toast {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-width: 280px;
  max-width: 320px;
  padding: 12px 16px;
  margin-top: 10px;
  border-radius: 5px;
  color: #222;
  font-family: sans-serif;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  opacity: 0;
  transform: translateY(20px);
  animation: slideIn 0.3s forwards, fadeOut 0.5s 2.5s forwards;
  position: relative;
	background:#fff;
}

.toast.success i.fa-circle-check {
  color: #28a745;
}

.toast.danger i.fa-triangle-exclamation{
  color: #dc3545;
}

	.close-btn i {
		color:#222;
	}

.toast.info i.fa-square-info{
  color: #bbb2b0;
}

.toast .toast-icon {
  margin-right: 12px;
  font-size: 18px;
}

.toast .toast-message {
  flex: 1;
}

.toast .close-btn {
  background: transparent;
  border: none;
  color: #fff;
  font-size: 16px;
  cursor: pointer;
  margin-left: 10px;
}

@keyframes slideIn {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeOut {
  to {
    opacity: 0;
    transform: translateY(20px);
  }
}
</style>
@endsection
