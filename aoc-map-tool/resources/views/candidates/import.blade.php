<?php
use App\Helpers\H;
use App\Helpers\LD;
use App\BranchLocations;
use App\Models\JobCategories;

$recruiters = H::getHelper("ConstantHelper")->getRecruiters('email');
$category = [];
$categoryList = JobCategories::query()->where('is_enabled', true)
    ->pluck('category')
    ->toArray();
$appSourceTypeArry = config('constant.appSourceType');
$appSourceType = array_keys($appSourceTypeArry);
?>
@extends('layouts.myaccount')
@section('title', 'Import Candidates')
@section('content')

<style>
    .importcandidate label {
        display: block;
    }
</style>

<div class="container-fluid importcandidate">
    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Import Candidate</h1>
        </div>
    </div>

    @if ($errors->any())
        <div class="col-sm-12">
            <div class="alert  alert-warning alert-dismissible fade show" role="alert">
                @foreach ($errors->all() as $error)
                <span>
                    <p>{{ $error }}</p>
                </span>
                @endforeach
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif
    @if ($message = Session::get('error'))
        <div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ $message }}
        </div>
    @endif
    @if (session('success'))
        <div class="col-sm-12">
            <div class="alert  alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
            </div>
        </div>
    @endif
    <form method="POST" enctype="multipart/form-data" id="import-form-validate" action="{{ route('candidates.processimport') }}">
        @csrf

        <input type="hidden" name="job_id" id="job_id">
        <input type="hidden" name="eid" id="eid">

        <div class="col-md-6">
            <div class="form-group row">
                <div class="col-md-12">
                    <label for="candidatecsv" style="width: 65%;">Import CSV </label>
                    <input type="file" class="form-control"  name="candidatecsv" id="candidatecsv" value=""/>
                </div>
                <div class="col-md-12 text-left">
                <a href="{{route('candidates.exporttemplate')}}"><i>Download Template</i></a>
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
                        <option value="{{$v['value']}}">{{$v['text']}}</option>
                        @endforeach
                    </select>
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
                    <label for="source">Source</label>
                    <select id="source" name="source" class="form-control">
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
                <div class="form-group row" id="jobDiv1">
                    <div class="col-md-6">
                        <label for="category">Category</label>
                        <select class="form-control" name="category" id="category1">
                            <option value="">Select Category</option>
                            @foreach($categoryList as $key => $value)
                            <option value="{{$value}}">{{$value}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="city">Search</label>
                        <input type="input" class="form-control job_search" id="job_search1" data-count="1" name="job_search" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="form-group row pt-2">
                <div class="col-md-6 text-left">
                    <button class="btn btn-primary" onclick="processImportCandidate(this);" type="button">Import Candidate</button>
                </div>
            </div>

        </div>
    </form>

</div>

<script type="text/javascript">

    function processImportCandidate(button){
        $("#import-form-validate").submit();
    }
    var sourceType = <?php echo json_encode($appSourceTypeArry);?>;
    $('#source_type').on('change', function() {
        let thisArry = sourceType[this.value];
        let options = '';
        if(this.value != ''){
            for(var i = 0, l = thisArry.length; i < l; i++){
                options += '<option value="' + thisArry[i]+ '">' + thisArry[i] + '</option>';
            }
        }
        $("#source").html(options);
    });

    $(document).on('keydown.autocomplete', "input.job_search", function() {
            $(this).autocomplete({
                classes: {
                    "ui-autocomplete": "search-requisition",
                },
                source: function(request, response) {
                    var input = this.element;
                    var selectedCnt = $(input).attr('data-count');
                    console.log($('#category'+selectedCnt+' option:selected').val());
                    $.getJSON("{{ route('getJobListByCategory') }}", {
                        category: $('#category'+selectedCnt+' option:selected').val(),
                        selected_element: selectedCnt,
                        term: $('#job_search'+selectedCnt).val()
                    }, response);
                },
                appendTo: "#job_search"+$(this.element).attr('data-count'),
                minLength: 2,
                select: function(event, ui) {
                    var current_job_id  = $('#job_id').val();
                    var current_eid  = $('#eid').val();
                    current_eid = (current_eid == '')?ui.item.eId: current_eid+','+ui.item.eId;
                    current_job_id = (current_job_id == '')?ui.item.id: current_job_id+','+ui.item.id;
                    $('#job_id').val(current_job_id);
                    $('#eid').val(current_eid);
                }
            });
        });

</script>

@endsection
