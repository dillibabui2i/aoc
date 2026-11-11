<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateClinicalInterviewQuestion extends Model
{
    use HasFactory;

    public $guarded = ['id'];
    public $table = 'candidate_clinical_interview_questions';
    public $casts = [
        'interview' => 'array'
    ];
}
