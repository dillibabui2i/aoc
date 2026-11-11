<?php

namespace App\Http\Controllers;

use App\FeatureFlags;
use App\Helpers\H;
use App\Helpers\LD;
use App\Candidates;
use App\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Notifications;
use App\MssqlRecruitertracker;
use App\CustomFields;

class EditableController extends Controller
{
    public function update(Request $request)
    {
        try {
            $oldvalues = '';
            $reqData = $request->all();
            $userId = Auth::user()->id;
            H::ExcelLog(Auth::user()->name,'globalEditor.log');
            H::ExcelLog(print_r($reqData,true),'globalEditor.log');
            if (isset($reqData['table']) && isset($reqData['pk']) && isset($reqData['name']) && array_key_exists('value',$reqData)) {
                $tableName = config('constant')['tables'][$reqData['table']];
                if ($tableName != '') {

                    // Preparing the notes before edit the actual fields
                    $noteEntity = isset(config('constant')['inlineEditEntity'][$tableName]) ? config('constant')['inlineEditEntity'][$tableName] : '';
                    $beforeEditnotes = [];
                    if($noteEntity != ''){
                        $currentValue = H::getCurrentValueOfFields($tableName,$reqData['pk'],$reqData['name']);
                        $beforeEditnotes = ['entity' => $noteEntity,'type'=>'edit', 'entity_id' => $reqData['pk'], 'notes' =>Auth::user()->name.' has changed '.$reqData['name'].' from '.$currentValue.' to '.$reqData['value'], 'completed' => 1];
                    }
                    if ($tableName == 'mssql_recruitertracker') {
                        $recruiterTrackerDetail10 = DB::table($tableName)->where('id', $reqData['pk'])->first();
                        $oldvalues = $recruiterTrackerDetail10->{$reqData['name']};
                        if (strpos($reqData['value'], '##') !== false) {
                            $ex = explode('##', $reqData['value']);
                            DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update([$reqData['name'] => $ex[0], 'user_id'=> $ex[1], 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId]);
                        } else {
                            if($reqData['name']=='Candidate_Current_Status'){
                                DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update([$reqData['name'] => $reqData['value'],'Status_Changed_Date'=> date('Y-m-d H:i:s'), 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId]);
                            }elseif($reqData['name']=='clinician_availability'){
                                //Update info
                                DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update([$reqData['name'] => $reqData['value'],'Status_Changed_Date'=> date('Y-m-d H:i:s'), 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId]);
                                $recruiterTrackerDetail = DB::table($tableName)->where('id', $reqData['pk'])->first();
                                $recruiterId = $recruiterTrackerDetail->user_id;
                                $recruiterAdminId = H::getRecruiterAdmin($recruiterTrackerDetail->user_id,true);
                                $title = 'Clinician availability added for '.$recruiterTrackerDetail->Candidate.' by branch';
                                //Notification to Recruiter
                                if($recruiterId) {
                                    Notifications::addNotification(['user_id'=>$recruiterId,'title'=>$title,'type'=>'trackerDetails','related_item'=>$reqData['pk'],'notes'=>$title]);
                                }
                                //Notification to Recruiter Admin
                                if($recruiterAdminId) {
                                    Notifications::addNotification(['user_id'=>$recruiterAdminId,'title'=>$title,'type'=>'trackerDetails','related_item'=>$reqData['pk'],'notes'=>$title]);
                                }
                            }elseif($reqData['name']=='GP'){
                                $bonusAmount = MssqlRecruitertracker::calculateBonusAmount($reqData);
                                DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update([$reqData['name'] => $reqData['value'],'bonus_amount'=> $bonusAmount, 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId]);
                                $oldvalues = $recruiterTrackerDetail10->bonus_amount;
                                if($bonusAmount != $oldvalues){
                                    H::getHelper('ActivityNotes')->addTrackerActivity($reqData['pk'],'bonus_amount',$bonusAmount,'update',$oldvalues);
                                }
                            }elseif($reqData['name']=='Skillset'){
                                $bonusAmount = MssqlRecruitertracker::calculateBonusAmount($reqData);
                                DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update([$reqData['name'] => $reqData['value'],'bonus_amount'=> $bonusAmount, 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId]);
                                $oldvalues = $recruiterTrackerDetail10->bonus_amount;
                                if($bonusAmount != $oldvalues){
                                    H::getHelper('ActivityNotes')->addTrackerActivity($reqData['pk'],'bonus_amount',$bonusAmount,'update',$oldvalues);
                                }
                            }else {
                                DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update([$reqData['name'] => $reqData['value'], 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId]);
                            }

                        }
                    } elseif ($tableName == 'NeedToHire_Accounts') {
                        if (strpos($reqData['value'], '##') !== false) {
                            $ex = explode('##', $reqData['value']);
                            $notesValue = $ex[0];
                            DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update([$reqData['name'] => $ex[0], 'user_id'=> $ex[1], 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId]);
                        } else {
                            $notesValue =  $reqData['value'];
                            DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update([$reqData['name'] => $reqData['value'], 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId]);
                        }
                        //Added activity history
                        $notes = H::getLoggedInUser()['name'].' has changed recruiter to '.$notesValue;
                        $notesData = ['entity' => 'nthAccount', 'entity_id' => $reqData['pk'], 'notes' =>$notes, 'completed' => 1];
                        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                    } elseif($tableName == 'candidates'){
                        DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update([$reqData['name'] => $reqData['value'], 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId,'syncToJobVite'=>1]);

                        if($reqData['name'] == 'firstName' || $reqData['name'] == 'lastName'){
                            $candidate = Candidates::find($reqData['pk']);
                            $name = $candidate->firstName.' '.$candidate->lastName;
                            DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update(['fullName'=>$name,'Search_Name'=>$name.' '.$candidate->firstName]);
                        }

                        if ($reqData['name'] == 'homePhone') {
                            DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update(['syncToJobvite' => 1]);
                        }

                    } elseif($tableName == 'NeedToHire_Checklist'){
                        if (LD::checkFeatureFlag('update-nth-bucket-started-date-on-bucket-change')) {
                            $steps = [];
                            $nthCheckListMainSteps = H::getHelper('NTHChecklist')->getFieldNameBuckets();
                            foreach ($nthCheckListMainSteps as $key => $value) {
                                $steps[] = $value;
                            }
                            $updateStartedField = $steps[$reqData['value']-1] . 'Started';
                            DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update([$reqData['name'] => $reqData['value'], 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId,$updateStartedField => date('Y-m-d H:i:s')]);
                        } else {
                            DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update([$reqData['name'] => $reqData['value'], 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId]);
                        }
                        //Added activity history
                        $currentStepValue = config('constant')['nthCheckListSteps'][$reqData['value']];
                        $notes = H::getLoggedInUser()['name'].' changed the current step to '.$currentStepValue;
                        $notesData = ['entity' => 'NeedToHire' ,'type' => 'NTH', 'entity_id' => $reqData['pk'], 'notes' =>$notes, 'completed' => 1];
                        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                    } elseif($tableName == 'mssql_patients' && $reqData['name'] == 'non_vbc'){
                        //set non_vbc to 1 on Yes and 0 otherwise  Trigger updating map data if client is on NTH
                        $myValue = '';
                        if($reqData['value'] == 'Yes') {
                            $myValue = 'Yes';
                        }
                        DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update([$reqData['name'] => $myValue, 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId]);
                        //see if this patient is on NTH and update map if needed
                        $nthAccount = DB::table('NeedToHire_Accounts')
                                        ->select('id','user_id')
                                        ->where('NeedToHire_Accounts.aoc_patient_id',$reqData['pk'])
                                        ->where('NeedToHire_Accounts.IsActive',1)->first();
                        if(isset($nthAccount->id)){
                            $nthAccountId = $nthAccount->id;
                            //now update map
                            H::ExcelLog('update NTH Map Record: ' . $nthAccountId, 'globalEditor.log');
                            H::getHelper('NTHChecklist')->AddEditNTHMapRecord($nthAccountId);
                            H::getHelper('NTHChecklist')->setClientAccountStatus($nthAccountId);
                        }
                    } elseif ($tableName == 'feature_flags') {
                        FeatureFlags::findOrFail($reqData['pk'])->update([$reqData['name'] => $reqData['value']]);
                    } elseif ($tableName == 'maps_coverageneeds') {
                        $steps = H::getHelper('ClientCareCoordinatorCoverage')->getBucketArray();
                        $updateStartedField = $reqData['value'] . '_activity_date';
                        $updateData = [$reqData['name'] => $reqData['value'],'last_activity_by' => $userId];
                        if($reqData['value'] != 'escalated') {
                            $updateData[$updateStartedField] =date('Y-m-d H:i:s');
                        }
                        DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update($updateData);
                        $finalnote = Auth::user()->name.' has changed the current step to '.$steps[$reqData['value']];
                        $notesData = ['entity' => 'coverage', 'notes' => $finalnote, 'entity_id' => $reqData['pk'], 'type' => 'coverageStep'];
                        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                        $nextStepCounter = H::getHelper('ClientCareCoordinatorCoverage')->getClientCareCoverageTracker($reqData['value'],'',1);
                        return response()->json(['success'=>true,'next_step_counter' => $nextStepCounter], 200);
                    } elseif ($tableName == 'school_setup_requests') {
                        H::ExcelLog('save Activity Note','updateTracker.log');
                        $updateData = ['notes' => $reqData['value']];
                        DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update($updateData);
                        $finalnote = 'Note was updated by '.Auth::user()->name.'<br>'.$reqData['value'];
                        $notesData = ['entity' => 'school-setup', 'notes' => $finalnote,
                        'type' => 'school', 'entity_id' => $reqData['pk']];
                        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                    } elseif(
                        isset($reqData['customfields']) &&
                        $reqData['customfields'] == true &&
                        $tableName == 'mssql_patients'
                    ) {
                        DB::table($tableName)
                        ->where('id', $reqData['pk'])
                        ->limit(1)
                        ->update([
                            $reqData['name'] => $reqData['value'],
                            'updated_at'=> date('Y-m-d H:i:s'),
                            'updated_by'=> $userId
                        ]);
                        if (!empty($reqData['preference_tag'])) {
                            $preferenceFieldsArray = [];
                            $preferenceFields = CustomFields::select('db_field_name', 'preference_tag')
                                            ->where('is_deleted', 0)
                                            ->where('category', 'Preferences')
                                            ->where('entity', 'client')
                                            ->get();
                            foreach ($preferenceFields as $field) {
                                $preferenceFieldsArray[$field->db_field_name] = $field->preference_tag;
                            }

                            if (count($preferenceFieldsArray) > 0) {
                                $patientPreferenceData = DB::table($tableName)
                                ->select(array_keys($preferenceFieldsArray))
                                ->where('id', $reqData['pk'])
                                ->get()
                                ->toArray();

                                $newPreferenceList = [];
                                foreach ($patientPreferenceData[0]??[] as $key => $val) {
                                    if ($val == 'Yes' && $key != 'aoc_cf_female_nurses') {
                                        $newPreferenceList[] = $preferenceFieldsArray[$key];
                                    }
                                    if ($val == 'No' && $key == 'aoc_cf_female_nurses') {
                                        $newPreferenceList[] = $preferenceFieldsArray[$key];
                                    }
                                }

                                DB::table($tableName)
                                ->where('id', $reqData['pk'])
                                ->limit(1)
                                ->update([
                                    'PreferenceList' => implode(",", $newPreferenceList)
                                ]);
                            }
                        }
                        $beforeEditnotes['notes'] = Auth::user()->name.' has changed '.
                        $reqData['label'].' from '.$currentValue.' to '.$reqData['value'];
                    } elseif(
                        isset($reqData['customfields']) &&
                        $reqData['customfields'] == true &&
                        $tableName == 'mssql_providers'
                    ) {
                        DB::table($tableName)
                        ->where('id', $reqData['pk'])
                        ->limit(1)
                        ->update([
                            $reqData['name'] => $reqData['value'],
                            'updated_at'=> date('Y-m-d H:i:s'),
                            'updated_by'=> $userId
                        ]);
                        $beforeEditnotes['notes'] = Auth::user()->name.' has changed '.
                        $reqData['label'].' from '.$currentValue.' to '.$reqData['value'];
                        $beforeEditnotes['entity'] = 'clinician';
                    } else {
                        DB::table($tableName)->where('id', $reqData['pk'])->limit(1)->update([$reqData['name'] => $reqData['value'], 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId]);
                    }
                    if ($tableName == 'mssql_recruitertracker') {
                        if($reqData['name']=='Candidate_Notes'){
                            H::ExcelLog('save Candidate_Notes','updateTracker.log');
                            $notesData = ['entity' => 'tracker', 'entity_id' => $reqData['pk'], 'notes' => $reqData['value'], 'completed' => 1];
                            H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                        }else{
                            H::ExcelLog('save Activity Note','updateTracker.log');
                            H::getHelper('ActivityNotes')->addTrackerActivity($reqData['pk'],$reqData['name'],$reqData['value'],'update',$oldvalues);
                        }
                        // $result = H::getHelper('Sync')->updateTrackerToMssql($reqData['pk'], 'Edit');
                    }
                    // Doing extra operation in this function because of the code length here very much
                    H::extraOperationOnInlineEditable($reqData);

                    //Adding notes for all edit
                    if(!empty($beforeEditnotes)){
                        H::getHelper('ActivityNotes')->setActivityNotes($beforeEditnotes);
                    }
                    if (!empty($reqData['category']) && (
                        $reqData['category'] == 'NTH' || $reqData['category'] == 'Preferences' ||
                        ($reqData['category'] == 'Client' && $reqData['name'] == 'CF_Skills')
                    )) {
                        $nthAccount = DB::table('NeedToHire_Accounts')
                                ->select('id')
                                ->where('NeedToHire_Accounts.aoc_patient_id', $reqData['pk'])
                                ->where('NeedToHire_Accounts.IsActive',1)
                                ->orderBy('NeedToHire_Accounts.id','desc')->first();
                        if(isset($nthAccount->id)){
                            $nthAccountId = $nthAccount->id;
                            H::getHelper('NTHChecklist')->AddEditNTHMapRecord($nthAccountId);
                            H::getHelper('NTHChecklist')->setClientAccountStatus($nthAccountId);
                        } else {
                            H::ExcelLog('No NTH found for : ' . $reqData['pk'],'editablecontroller.log');
                            return;
                        }
                    }
                    return response()->json(['success'=>true], 200);
                } else {
                    return response()->json(['success'=>false, 'msg'=>'Table is not defined'], 200);
                }
            } else {
                return response()->json(['success'=>false, 'msg'=>'table,pk,name and value are mandatory'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['success'=>false, 'msg'=>$e->getMessage()], 200);
        }
    }
}
