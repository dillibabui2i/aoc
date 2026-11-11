<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\MssqlRecruitertracker;
use App\Helpers\H;

class Intake extends Model
{
    use HasFactory;
    protected $table = 'intake';

    public static function getRecruiterTrackerId($candiateId){
        if(!empty($candiateId)){
            $id = MssqlRecruitertracker::where('aoc_candidate_id',
            $candiateId)->value('id');
            if(!empty($id)){
                return $id;
            }else{
                return 0;
            }
        }else{
            return 0;
        }
    }

    public static function deleteCandiateData($intakeId, $newCandidateArray){
        $oldCandidateArray = MssqlRecruitertracker::select('mssql_recruitertracker.aoc_candidate_id')
        ->where('aoc_intake_id', $intakeId)->pluck('aoc_candidate_id')
        ->toArray();
        if(empty($newCandidateArray)){
            if(!empty($oldCandidateArray)){
                foreach($oldCandidateArray as $oldcandidate){
                    MssqlRecruitertracker::where('aoc_candidate_id',$oldcandidate)->delete();
                    Candidates::where('id',$oldcandidate)->delete();
                    H::ExcelLog('Intake Form - Candidates deleted::'.$oldcandidate,'intake.log');
                }
            }
        }elseif(empty($oldCandidateArray)){
            return true;
        }else{
            foreach($oldCandidateArray as $oldcandidate){
                if(!in_array($oldcandidate, $newCandidateArray)){
                    MssqlRecruitertracker::where('aoc_candidate_id',$oldcandidate)->delete();
                    Candidates::where('id',$oldcandidate)->delete();
                    H::ExcelLog('Intake Form - Candidates deleted::'.$oldcandidate,'intake.log');
                }
            }
        }
    }
}
