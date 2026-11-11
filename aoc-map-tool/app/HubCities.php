<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HubCities extends Model
{
    use HasFactory;
    protected $table = 'NeedToHire_Hub';
    protected $fillable = ['State', 'ShortState', 'City', 'HUB', 'Branch', 'MilesFromHub', 'Population', 'TextBlastRadius', 'Notes','created_at','created_by','updated_at','updated_by'];
}
