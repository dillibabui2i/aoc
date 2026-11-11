<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $states = H::getHelper("ConstantHelper")->getDashboardState();
    $adsType = config('constant')['adsType'];
    $category = config('constant')['adsCategory'];
?>

<x-modal>
    <x-slot name="id">adsInfoForm</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">Ad Information</x-slot>
    <x-slot name="extraHeader"></x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row">
            <form action="" id="saveAdsForm">
                @if($socialMediaId > 0)
                <input type="hidden" name="id" id="id" value="{{$socialMedia->id}}">
                <div class="col-md-12 tracker-details mb-3">
                    <div class="card-cnt d-flex">
                        <div class="card  mr-5">
                            <div class="card-body">
                            <h5 class="card-title bg-blue">Client Info</h5>
                                <div class="detail-section">
                                    <div class="row">
                                        <div class="col-md-6">{{$patientsDetails->Patient_FirstName.' '.$patientsDetails->Patient_MiddleName.' '.$patientsDetails->Patient_LastName}}</div>
                                        <div class="col-md-6">Zip Code: {{$patientsDetails->Patient_Zip}}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">{{$patientsDetails->PatientAccount}}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">Payer: {{$patientsDetails->Payer}}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">Media Consent: {{strpos(strtolower($patientsDetails->PreferenceList),"media consent form on file") !== false ? 'Yes' : 'No'}}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">Discipline: {{$patientsDetails->CF_ClinicianDiscipline}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                            <h5 class="card-title bg-blue">NTH Details</h5>
                                <div class="detail-section">
                                    <div class="row">
                                        <div class="col-md-6">NTH Status: {{$needToHireAccounts->Status}}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">Date added to NTH: {{isset($needToHireAccounts->created_at) ? H::getFormatedDate($needToHireAccounts->created_at) : ''}}</div>
                                        <div class="col-md-6">Days On NTH: <?php if(isset($needToHireAccounts->created_at)) { $earlier = new DateTime($needToHireAccounts->created_at); $later = new DateTime(now()); echo $later->diff($earlier)->format("%a");} ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">Services Needed: {{$needToHireAccounts->ServicesNeeded}}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">Diagnosis: {{$socialMedia->skills_diagnosis}}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">Shifts Needed: {{$socialMedia->shifts_needed}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 tracker-details mb-3">
                    <div class="card-cnt d-flex">
                        <div class="card  mr-5">
                            <div class="card-body">
                            <h5 class="card-title bg-blue">The Sale</h5>
                                <div class="detail-section">
                                    <p class="card-text text-justify">
                                        {{$patientsDetails->CF_TheSale}}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                            <h5 class="card-title bg-blue">NTH Notes</h5>
                                <div class="detail-section">
                                    <p class="card-text text-justify">
                                        {{$patientsDetails->NTHNotes}}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <input type="hidden" name="id" id="id" value="0">
                @endif
                <h5>Ad Details</h5>
                <div class="col-md-12 tracker-details mb-3">
                <div class="row mt-4">   
                <div class="col-md-6">
                    <label for="ad_name">Ad Name</label>
                    <?php if(LD::checkFeatureFlag('social-media-ads-add-zip-code-load-default')) { 
                                 $patientName = '';
                                 if(!empty($patientsDetails)){
                                     $patientName = $patientsDetails->Patient_FirstName.' '.$patientsDetails->Patient_LastName.' Ad';
                                 } 
                            ?>
                            <input type="input" class="form-control" id="ad_name" name="ad_name" value="{{isset($socialMedia->ad_name) ? $socialMedia->ad_name:$patientName}}" />
                    <?php } else { ?>
                        <input type="input" class="form-control" id="ad_name" name="ad_name" value="{{isset($socialMedia->ad_name) ? $socialMedia->ad_name : ''}}" />
                    <?php } ?>
                </div>
                <div class="col-md-6 socialmediapl">
                    <label for="datepicker">Start Date</label>
                    <input type="input" class="form-control" id="datepicker" name="start_date" value="{{isset($socialMedia->start_date) ? H::getFormatedDate($socialMedia->start_date) : ''}}" />
                </div>
                </div>
                <div class="row">
                <div class="col-md-6">
                    <label for="state">State</label>
                    <select name="state" id="state" class="form-control">
                        <option value="">--- Select One ----</option>
                        @foreach($states as $k => $v)
                            <option value="{{$v['value']}}" {{isset($socialMedia->state) && $socialMedia->state == $v['value'] ? "selected='selected'": "" }}>{{$v['text']}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 socialmediapl">
                    <label for="city">City</label>
                    <input type="input" class="form-control" id="city" name="city" value="{{isset($socialMedia->city) ? $socialMedia->city : ''}}" />
                </div>
                </div>
                    <?php if(LD::checkFeatureFlag('social-media-ads-add-zip-code-load-default')) { 
                                $zipCode = '';
                                if($socialMedia) {
                                    $zipCode = $socialMedia->zipcode;
                                    if(empty($zipCode)){
                                        if($patientsDetails) {
                                            $zipCode = $patientsDetails->Patient_Zip;
                                        }
                                        
                                    } 
                                }
                                

                        
                        ?>

                        <div class="row">
                            
                            <div class="col-md-6">
                                <label for="budget">Zip</label>
                                <input type="input" class="form-control" id="zipcode" name="zipcode" value="{{isset($zipCode) ? $zipCode : ''}}" />
                            </div>

                            <div class="col-md-6">
                                <label for="budget">Budget</label>
                                <input type="input" class="form-control" id="budget" name="budget" value="{{isset($socialMedia->budget) ? $socialMedia->budget : ''}}" />
                            </div>
                            
                        </div>


                        <div class="row">
                            <div class="col-md-6">
                                <label for="ad_type">Ad Type</label>
                                <select name="ad_type" id="ad_type" class="form-control">
                                    <option value="">--- Select One ----</option>
                                    @foreach($adsType as $k => $v)
                                        <option value="{{$v}}" {{isset($socialMedia->ad_type) && $socialMedia->ad_type ==$v ? "selected='selected'": "" }}>{{$v}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <?php if(LD::checkFeatureFlag('social-media-dashboard-report')) { ?>
                            <div class="col-md-6">
                                <label for="ad_type">Category</label>
                                <select name="category" id="category" class="form-control">
                                    <option value="">--- Select One ----</option>
                                    @foreach($category as $k => $v)
                                        <option value="{{$v}}" {{(isset($socialMedia->category) && $socialMedia->category == $v) || (isset($needToHireAccounts->ServicesNeeded) && $needToHireAccounts->ServicesNeeded == $v) ? "selected='selected'": "" }}>{{$v}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <?php } ?>

                        </div>
                    
                    <?php } else { ?>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="budget">Budget</label>
                                <input type="input" class="form-control" id="budget" name="budget" value="{{isset($socialMedia->budget) ? $socialMedia->budget : ''}}" />
                            </div>
                            <div class="col-md-6 socialmediapl">
                                <label for="ad_type">Ad Type</label>
                                <select name="ad_type" id="ad_type" class="form-control">
                                    <option value="">--- Select One ----</option>
                                    @foreach($adsType as $k => $v)
                                        <option value="{{$v}}" {{isset($socialMedia->ad_type) && $socialMedia->ad_type ==$v ? "selected='selected'": "" }}>{{$v}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <?php if(LD::checkFeatureFlag('social-media-dashboard-report')) { ?>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="ad_type">Category</label>
                                    <select name="category" id="category" class="form-control">
                                        <option value="">--- Select One ----</option>
                                        @foreach($category as $k => $v)
                                            <option value="{{$v}}" {{(isset($socialMedia->category) && $socialMedia->category == $v) || (isset($needToHireAccounts->ServicesNeeded) && $needToHireAccounts->ServicesNeeded == $v) ? "selected='selected'": "" }}>{{$v}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        <?php } ?>

                    <?php } ?>



                   
            </div>
                <div class="col-md-12 pt-5">
                    <div class="col-md-7 text-right">
                        <button type="button" id="saveAdsFormBtn" onclick="saveAdsForm()" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
    </x-slot>
</x-modal>
<script>
    $('#datepicker').datepicker();
    $('#datepicker').datepicker().bind('click', function() {
        $('#ui-datepicker-div').appendTo($('#datepicker').parent());
    });
</script>
