<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MssqlFacilities extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'mssql_facilities';
    public $timestamps = true;

    public static function getFacilitiesAttrById($id,$attr){
        $data = MssqlFacilities::select($attr)->find($id);
        return isset($data->{$attr}) ? $data->{$attr} : '';
    }
}
