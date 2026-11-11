<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MssqlPatientStatus extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'mssql_patientstatus';
    public $timestamps = true;
}
