<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;
    protected $table = 'conversation';
    protected $fillable = ['aoc_provider_id',
    'aoc_candidate_id',
    'candidate_mobile',
    'sid',
    'messaging_service_sid',
    'chat_service_sid',
    'friendly_name',
    'unique_name',
    'api_response',
    'created_at',
    'updated_at'];
}
