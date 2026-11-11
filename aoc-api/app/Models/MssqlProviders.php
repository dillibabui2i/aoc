<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MssqlProviders extends Model
{
    use HasFactory;
    protected $table = 'mssql_providers';

    protected static function getProviderDetails($id, $fields)
    {
        return self::select($fields)->where('id', $id)->first();
    }
}
