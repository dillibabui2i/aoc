<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientVacation extends Model
{
    use HasFactory;
    protected $table = 'patient_vacation';
    protected $fillable = ['aoc_patient_id', 'from_date', 'to_date', 'destination', 'with_nurse', 'comment'];
}
