<?php
use App\Helpers\LD;
    if(LD::checkFeatureFlag('ccc-followup-calls-add-option-to-nurse-relationship')) {
        $options = ['','Yes','No','No clinicians in the home'];
    } else {
        $options = ['','Yes','No'];
    }
   
    $currentIssues = config("constant.nurseRelationCurrentIssues");

    $nurseRelationClinicialTraining = config("constant.nurseRelationClinicialTraining");
    $nurseRelationNeglect = config("constant.nurseRelationNeglect");
    $nurseRelationBehaviour = config("constant.nurseRelationBehaviour");
    $nurseRelationPersonality = config("constant.nurseRelationPersonality");
?>

<section class="tab-content-nurserelation">
    <div class="main-content">
        <div class="tab-content-area col-md-12 pt-3 pb-3">

            <div class="form-row">
                <div class="form-group col-md-8 pb-0 mb-0">
                    <label
                        for="nurserelation_relation"
                        class="col-md-8 p-0 m-0 text-left"
                    >Do you have a good relationship with all of the nurses in the home?</label>
                    <select
                        class="form-control"
                        id="nurserelation_relation"
                        name="nurserelation[relation]">
                        @foreach ($options as $option)
                            <option value="{{$option}}">{{$option}}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            
            <div class="form-row" style="display: none" id="relation_issue_container">
                @foreach($nurseRelation as $nurse)
                    @if(substr($nurse->ProviderName,0,3)== "_Un")
                        @continue;
                    @endif
                    <div class="form-row col-md-12 pb-0 mb-0 pt-3">
                        <label
                            for="nurserelation_relation"
                            class="col-md-6 p-0 m-0 text-left"
                        ><strong>{{$nurse->ProviderName}}</strong></label>
                        <input type="hidden" name="nurserelation[currentissues][{{$nurse->id}}][nursename]" id="nurserelation_currentissues_{{$nurse->id}}_name" value="{{$nurse->ProviderName}}"> 
                    </div>


                    <div class="form-row col-md-12 pb-0 mb-0">
                        <div class="form-group  col-md-4  mb-0 p-0">
                            <label
                                for="nurse_currentissues_{{$nurse->id}}"
                                class="col-md-6 p-0 m-0 text-left"
                            >What are the current issues?</label>
                            <select
                                class="form-control currentissue_dropdown"
                                id="nurse_currentissues_{{$nurse->id}}"
                                name="nurserelation[currentissues][{{$nurse->id}}][issue]" data-nurseid="{{$nurse->id}}" >
                                @foreach ($currentIssues as $issue)
                                    <option value="{{$issue}}">{{$issue}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group  col-md-4 pb-0 mb-0" style="display: none;" id="issue_container_{{$nurse->id}}">
                            <label
                                for="issue_container_{{$nurse->id}}"
                                class="col-md-6 p-0 m-0 text-left"
                            >Details of issue(s)...</label>
                            <div class="form-group">
                                <select
                                    class="form-control currentissuedetails_dropdown"
                                    id="nurse_currentissues_clinicialtraining_{{$nurse->id}}"
                                    name="nurserelation[currentissues][{{$nurse->id}}][clinicialtraining]" 
                                    data-nurseid="{{$nurse->id}}"
                                    style="display: none;">
                                    <option value="">Select One</option>
                                    @foreach ($nurseRelationClinicialTraining as $clinicialTraining)
                                        <option value="{{$clinicialTraining}}">{{$clinicialTraining}}</option>
                                    @endforeach
                                </select>

                                <select
                                    class="form-control currentissuedetails_dropdown"
                                    id="nurse_currentissues_relationneglect_{{$nurse->id}}"
                                    name="nurserelation[currentissues][{{$nurse->id}}][relationneglect]" 
                                    data-nurseid="{{$nurse->id}}"
                                    style="display: none;">
                                    <option value="">Select One</option>
                                    @foreach ($nurseRelationNeglect as $relationneglect)
                                        <option value="{{$relationneglect}}">{{$relationneglect}}</option>
                                    @endforeach
                                </select>

                                <select
                                    class="form-control currentissuedetails_dropdown"
                                    id="nurse_currentissues_behaviour_{{$nurse->id}}"
                                    name="nurserelation[currentissues][{{$nurse->id}}][behaviour]" 
                                    data-nurseid="{{$nurse->id}}"
                                    style="display: none;">
                                    <option value="">Select One</option>
                                    @foreach ($nurseRelationBehaviour as $behaviour)
                                        <option value="{{$behaviour}}">{{$behaviour}}</option>
                                    @endforeach
                                </select>

                                <select
                                    class="form-control currentissuedetails_dropdown"
                                    id="nurse_currentissues_personality_{{$nurse->id}}"
                                    name="nurserelation[currentissues][{{$nurse->id}}][personality]" 
                                    data-nurseid="{{$nurse->id}}"
                                    style="display: none;">
                                    <option value="">Select One</option>
                                    @foreach ($nurseRelationPersonality as $personality)
                                        <option value="{{$personality}}">{{$personality}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>


                        <div class="form-group  col-md-4 pb-0 mb-0" style="display: none;" id="skills_container_{{$nurse->id}}">
                            <label
                                for="issue_container_{{$nurse->id}}"
                                class="col-md-3 p-0 m-0 text-left"
                            >Skills- </label>
                            <div class="form-group">
                                <select
                                    class="form-control skills_dropdown"
                                    id="nurse_currentissues_skills_{{$nurse->id}}"
                                    name="nurserelation[currentissues][{{$nurse->id}}][skills]" 
                                    style="display: none;">
                                    <option value="">Select One</option>
                                    @foreach ($clientskills as $skills)
                                        <option value="{{$skills}}">{{$skills}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<script>
    $(document).on("change",".currentissuedetails_dropdown",function(){
        nurseID = $(this).attr("data-nurseid");
        var dropValue = $(this).val();
        if(dropValue=="Specialized skills") {
            
            $("#skills_container_"+nurseID).show();
            $("#nurse_currentissues_skills_"+nurseID).show();
        } else {
            $("#skills_container_"+nurseID).hide();
            $("#nurse_currentissues_skills_"+nurseID).hide();
        }
    });
    
    $(document).on("change","#nurserelation_relation",function(){
        
        if($(this).val()=="No") {
            $("#relation_issue_container").show();
        } else {
            $("#relation_issue_container").hide();
        }
    });

    $(document).on("change",".currentissue_dropdown",function(){
        nurseID = $(this).attr("data-nurseid");
        var dropValue = $(this).val();

       
        if(dropValue!='None') {
            $("#issue_container_"+nurseID).show();

            $("#nurse_currentissues_clinicialtraining_"+nurseID).hide();
            $("#nurse_currentissues_relationneglect_"+nurseID).hide();
            $("#nurse_currentissues_behaviour_"+nurseID).hide();
            $("#nurse_currentissues_personality_"+nurseID).hide();

            if(dropValue=='Clinicial training') {
                $("#nurse_currentissues_clinicialtraining_"+nurseID).show();
            } else if(dropValue=='Neglect/abuse/exploitation'){
                $("#nurse_currentissues_relationneglect_"+nurseID).show();

            } else if(dropValue=='Professional conduct/Behaviour'){
                $("#nurse_currentissues_behaviour_"+nurseID).show();

            } else if(dropValue=='Personality/Fit'){
                $("#nurse_currentissues_personality_"+nurseID).show();

            }

        } else {
            $("#issue_container_"+nurseID).hide();
            $("#nurse_currentissues_clinicialtraining_"+nurseID).hide();
            $("#nurse_currentissues_relationneglect_"+nurseID).hide();
            $("#nurse_currentissues_behaviour_"+nurseID).hide();
            $("#nurse_currentissues_personality_"+nurseID).hide();
        }

    });
</script>
