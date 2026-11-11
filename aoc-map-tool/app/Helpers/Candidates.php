<?php
namespace App\Helpers;

use App\User;

class Candidates
{
    public function getCandidates()
    {
        //need to remove this helper
        return 1;
    }

    /*
        $email =  candidates.jobPrimaryRecruiter
    */

    public static function getRecruiterNameByCandidateEmail($email){
        if($email != ''){
            $user = User::where('email',$email)->first();
            return isset($user->name) ? $user->name : '';
        }else{
            return '';
        }
    }
}
