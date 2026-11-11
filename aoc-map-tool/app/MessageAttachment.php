<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class MessageAttachment extends Model
{
    use HasFactory;
    protected $table = 'message_attachment';
}
