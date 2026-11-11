<?php

namespace App\Helpers;

use App\Notes;
use App\RecruitingSchedule;
use App\MssqlRecruitertracker;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\NeedToHireAccounts;
use App\NeedToHireChecklist;
use App\SocialMedia;
use App\Helpers\LD;

class ActivityNotes
{
    public function setActivityNotes($data)
    {
        try {
            $user = H::getLoggedInUser()['id'];
            $data['created_by'] = $user;
            $data['created_at'] = Carbon::now();
            $data['updated_at'] = Carbon::now();
            $insert = Notes::insert($data);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function setActivityNotesFromConsole($data)
    {
        try {
            $user = User::SYSTEM_ID;
            $data['created_by'] = $user;
            $data['created_at'] = Carbon::now();
            $data['updated_at'] = Carbon::now();
            $insert = Notes::insert($data);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getActivityNotes(...$arguments)
    {
        $arg = $arguments;
        $notes = Notes::select('notes.*', 'users.name')->where(function ($query) use ($arg) {
            foreach ($arg as $argument) {
                $query->orWhere(function ($query) use ($argument) {
                    foreach ($argument as $key => $value) {
                        $query->where($key, $value);
                    }
                });
            }
        })->leftJoin('users', function ($join) {
            $join->on('notes.created_by', '=', 'users.id');
        })->orderBy('created_at', 'DESC')->get();

        if (count($notes)) {
            return $notes;
        } else {
            return [];
        }
    }

    public function addTrackerActivity($id,$fieldName,$value, $action = 'update', $oldvalues = ''){
        $createdByName = H::getLoggedInUser()['name'];
        $fieldLabels = [
            'Total_Hours' => 'Total Hours',
            'Agreed_Pay_Rate' => 'Pay Rate',
            'GP' => 'GP',
            'Approved_By' => 'Approved By',
            'Clinician_ID' => 'ClinicianID',
            'Candidate_Notes' => 'Candidate Notes',
            'aoc_patient_id' => "Client",
            'Bonus_Status' => 'Bonus Status',
            'Bonus_Paid_Date' => 'Bonus Paid Date',
            'whiteboard_row' => 'Whiteboard Row',
            'bonus_amount' => 'Bonus Amount'
        ];
        if($createdByName != ''){
            switch ($fieldName) {
                case 'Agreed_Pay_Rate':
                    $value = "$".$value;
                    break;
                case 'GP':
                    $value = $value.'%';
                    break;

                default:
                    # code...
                    break;
            }
            $fieldName = isset($fieldLabels[$fieldName]) ? $fieldLabels[$fieldName] : $fieldName;
            if ($action == 'update') {
                if ($fieldName == 'Pay Rate') {
                    $oldvalues = $oldvalues == '' ? 0 : $oldvalues;
                    $note = $fieldName. ' was changed from ' . "$".$oldvalues . ' to '.$value. ' by '.$createdByName;
                }elseif($fieldName == 'Bonus Amount'){
                    $note = 'The bonus amount was adjusted from ' . "$".$oldvalues .'  to $'.$value.
                    ' due to changes in the tracker details. by '.$createdByName;
                } else {
                    if($oldvalues != '' && $oldvalues != NULL && $oldvalues != 'NULL' && $oldvalues != '0.00'){
                        $note = $fieldName. ' was changed from '.$oldvalues.' to '.$value. ' by '.$createdByName;
                    }else{
                        $note = $fieldName. ' was changed from [EMPTY] to '.$value. ' by '.$createdByName;
                    }
                }
            } else {
                $note = $fieldName. ' was set to '.$value. ' by '.$createdByName;
            }
            $notesData = ['entity' => 'tracker-activity', 'entity_id' => $id, 'notes' => $note, 'completed' => 1];
            H::getHelper('ActivityNotes')->setActivityNotes($notesData);
        }
    }
    public function getEmailNotes($trackerId) {
        $notes = Notes::select('notes.*')->where('notes.tracker_id',$trackerId)
                ->where('notes.entity','email')->get();
        return $notes;
    }
    public function getEmailNotesByClient($clientId) {
        $notes = Notes::select('notes.*')->whereIn('notes.patient_id',$clientId)
                ->where('notes.entity','email')->get();
        return $notes;
    }

    public function getEmailContent($id){
        $notes = Notes::select('notes.*')->where('notes.id',$id)
        ->where('notes.entity','email')->get();
        return $notes;
    }

    public function getActivityNotesByRecruiterTracker($trackerId) {
        $notes = Notes::select('notes.*', 'users.name')
                ->where('notes.tracker_id',$trackerId)
                ->orWhere(function ($query) use($trackerId) {
                    $query->where('notes.entity_id',$trackerId)->where('notes.entity','tracker');
                })
                ->leftJoin('users', function ($join) {
                    $join->on('notes.created_by', '=', 'users.id');
                })->get();
        return $notes;

    }
    public function getActivityNotesByCandidates($candidateId) {
        $notes = Notes::select('notes.*', 'users.name')
                ->where('notes.entity_id',$candidateId)
                ->where('notes.entity','candidate')
                ->leftJoin('users', function ($join) {
                    $join->on('notes.created_by', '=', 'users.id');
                })->get();
        return $notes;

    }
    public function getActivityNotesByClinician($clinicianId) {
        $notes = Notes::select('notes.*', 'users.name')
                ->where('notes.entity_id',$clinicianId)
                ->where('notes.entity','clinician')
                ->leftJoin('users', function ($join) {
                    $join->on('notes.created_by', '=', 'users.id');
                })->get();
        return $notes;

    }
    public function getActivityNotesByClient($clientId) {
        $notes = Notes::select('notes.*', 'users.name')->whereIn('notes.patient_id',$clientId)
                ->orWhere(function ($query) use($clientId) {
                    $query->whereIn('notes.entity_id',$clientId)->where('notes.entity','client');
                })->leftJoin('users', function ($join) {
                    $join->on('notes.created_by', '=', 'users.id');
                })->get();
        return $notes;
    }

    public function getRecruitingScheduleByClient($clientId) {
        $schedule = RecruitingSchedule::select('recruiting_schedule.*',
        'mssql_patients.PatientName',
        DB::raw("CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) as providerName"),
        DB::raw("CONCAT(candidates.firstName,' ',candidates.lastName) as candidateName"),
        'schedule_types.type', 'users.name',
        'branch_locations.branch')
        ->whereIn('recruiting_schedule.aoc_patient_id',$clientId)
        ->leftJoin('users', function ($join) {
            $join->on('recruiting_schedule.created_by', '=', 'users.id');
        })
        ->leftJoin('schedule_types', function ($join) {
            $join->on('schedule_types.id', '=', 'recruiting_schedule.type_id');
        })
        ->leftJoin('mssql_patients', function ($join) {
            $join->on('mssql_patients.id', '=', 'recruiting_schedule.aoc_patient_id');
        })
        ->leftJoin('candidates', function ($join) {
            $join->on('candidates.id', '=', 'recruiting_schedule.aoc_candidate_id');
        })
        ->leftJoin('mssql_providers', function ($join) {
            $join->on('mssql_providers.id', '=', 'recruiting_schedule.aoc_provider_id');
        })
        ->leftJoin('branch_locations', function ($join) {
            $join->on('branch_locations.id', '=', 'recruiting_schedule.ipea_location_id');
        })->get();

        return $schedule;
    }


    public function getRecruitingScheduleByRecruiterTracker($trackerId) {
        $schedule = RecruitingSchedule::select('recruiting_schedule.*','mssql_patients.PatientName','schedule_types.type', 'users.name', 'branch_locations.branch')
        ->where('recruiting_schedule.tracker_id',$trackerId)
        ->leftJoin('users', function ($join) {
            $join->on('recruiting_schedule.created_by', '=', 'users.id');
        })
        ->leftJoin('schedule_types', function ($join) {
            $join->on('schedule_types.id', '=', 'recruiting_schedule.type_id');
        })
        ->leftJoin('mssql_patients', function ($join) {
            $join->on('mssql_patients.id', '=', 'recruiting_schedule.aoc_patient_id');
        })
        ->leftJoin('branch_locations', function ($join) {
            $join->on('branch_locations.id', '=', 'recruiting_schedule.ipea_location_id');
        })->get();

        return $schedule;
    }

    public function getRecruitingScheduleByCandidates($candidateId) {
        $schedule = RecruitingSchedule::select('recruiting_schedule.*','mssql_patients.PatientName','schedule_types.type', 'users.name', 'branch_locations.branch')
        ->where('recruiting_schedule.aoc_candidate_id',$candidateId)
        ->leftJoin('users', function ($join) {
            $join->on('recruiting_schedule.created_by', '=', 'users.id');
        })
        ->leftJoin('schedule_types', function ($join) {
            $join->on('schedule_types.id', '=', 'recruiting_schedule.type_id');
        })
        ->leftJoin('mssql_patients', function ($join) {
            $join->on('mssql_patients.id', '=', 'recruiting_schedule.aoc_patient_id');
        })
        ->leftJoin('branch_locations', function ($join) {
            $join->on('branch_locations.id', '=', 'recruiting_schedule.ipea_location_id');
        })->get();

        return $schedule;
    }

    public function getRecruitingScheduleByClinician($clinicianId) {
        $schedule = RecruitingSchedule::select('recruiting_schedule.*','mssql_patients.PatientName','schedule_types.type', 'users.name', 'branch_locations.branch')
        ->where('recruiting_schedule.aoc_provider_id',$clinicianId)
        ->leftJoin('users', function ($join) {
            $join->on('recruiting_schedule.created_by', '=', 'users.id');
        })
        ->leftJoin('schedule_types', function ($join) {
            $join->on('schedule_types.id', '=', 'recruiting_schedule.type_id');
        })
        ->leftJoin('mssql_patients', function ($join) {
            $join->on('mssql_patients.id', '=', 'recruiting_schedule.aoc_patient_id');
        })
        ->leftJoin('branch_locations', function ($join) {
            $join->on('branch_locations.id', '=', 'recruiting_schedule.ipea_location_id');
        })->get();

        return $schedule;
    }

    public function getActivityNotesByTypeAndRecruiterTracker($trackerId) {
        $notes = Notes::select('notes.*', 'users.name')
        ->where('notes.entity_id',$trackerId)
        ->where('notes.entity','tracker-activity')
        ->leftJoin('users', function ($join) {
            $join->on('notes.created_by', '=', 'users.id');
        })->get();
        return $notes;
    }

    public function getActivityNotesByTypeAndClient($clientId) {
        $notes = Notes::select('notes.*', 'users.name')
        ->whereIn('notes.entity_id',$clientId)
        ->where('notes.entity','client-activity')
        ->leftJoin('users', function ($join) {
            $join->on('notes.created_by', '=', 'users.id');
        })->get();
        return $notes;
    }
    public function getActivityNotesByTypeAndCandidates($candidateId) {
        $notes = Notes::select('notes.*', 'users.name')
        ->where('notes.candidate_id',$candidateId)
        ->where(function($q) {
            $q->where('notes.entity','tracker')->orWhere('notes.entity','tracker-activity');
        })
        ->leftJoin('users', function ($join) {
            $join->on('notes.created_by', '=', 'users.id');
        })->get();
        return $notes;
    }

    public function getActivityNotesByTypeAndClinician($clinicianId) {
        $notes = Notes::select('notes.*', 'users.name')
        ->where('notes.provider_id',$clinicianId)
        ->where('notes.entity','tracker-activity')
        ->leftJoin('users', function ($join) {
            $join->on('notes.created_by', '=', 'users.id');
        })->get();
        return $notes;
    }

    public function getDateFormatForTrackerHistory($date) {
        if (!empty($date)) {
            $dates = new \DateTime($date);
            if(LD::checkFeatureFlag('add-year-in-activity-notes-date-formate')){
                $formattedDate1 = $dates->format('M d, Y');
            }else{
                $formattedDate1 = $dates->format('M d');
            }
            $formattedDate2 = $dates->format('h:i A');
            return $formattedDate1.' at '.$formattedDate2;
        } else {
            return '-';
        }
    }

    public function getAppointmentDate($date) {
        if (!empty($date)) {
            $dates = new \DateTime($date);
            $formattedDate1 = $dates->format('m/d/Y');
            return $formattedDate1;
        } else {
            return '-';
        }
    }

    public function getActivityOfTriageCall($triageId) {
        $notes = Notes::select('notes.*', 'users.name')
        ->where('notes.entity_id',$triageId)
        ->where('notes.entity','triage')
        ->leftJoin('users', function ($join) {
            $join->on('notes.created_by', '=', 'users.id');
        })->get();
        return $notes;
    }
    public function getCoverageTrackerActivity($coverageId) {
        $notes = Notes::select('notes.*', 'users.name')
        ->where('notes.entity_id',$coverageId)
        ->where('notes.entity','coverage')
        ->leftJoin('users', function ($join) {
            $join->on('notes.created_by', '=', 'users.id');
        })->get();
        return $notes;
    }

    public function getNthNotesByClientId($clientId){
        $notes = '';
        $nthID = NeedToHireAccounts::select('id')->whereIn('aoc_patient_id', $clientId)->pluck('id');
        if($nthID->isNotEmpty()){
            $nthCheckListId = NeedToHireChecklist::select('id')->whereIn('aoc_nthaccounts_id', $nthID)->pluck('id');
            $notes = Notes::select('notes.*', 'users.name')
                    ->Where(function ($query) use($nthCheckListId) {
                        $query->whereIn('notes.entity_id',$nthCheckListId)->where('notes.entity','NeedToHire');
                    })->orWhere(function ($query) use($nthID) {
                        $query->whereIn('notes.entity_id',$nthID)->where('notes.entity','nthAccount');
                    })
                    ->leftJoin('users', function ($join) {
                        $join->on('notes.created_by', '=', 'users.id');
                    })->get();
            return $notes;
        }
        return $notes;
    }

    public function getSocialNotesByClientId($clientId){
        $notes = '';
        $nthid = NeedToHireAccounts::select('id')->whereIn('aoc_patient_id', $clientId)->pluck('id');
        $socialId = SocialMedia::select('id')->whereIn('aoc_nthaccounts_id', $nthid)->pluck('id');
        if($socialId->isNotEmpty()){
            $notes = Notes::select('notes.*', 'users.name')
            ->Where(function ($query) use($socialId) {
                $query->whereIn('notes.entity_id',$socialId)->where('notes.entity','social');
            })
            ->leftJoin('users', function ($join) {
                $join->on('notes.created_by', '=', 'users.id');
            })->get();
            return $notes;
        }
        return $notes;
    }
}
