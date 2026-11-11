<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class WhiteBoard extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'whiteboard';
    protected $guarded = [];

    public static function booted() {

        parent::booted();

        static::addGlobalScope('exclude_deleted', function (Builder $builder) {
			$builder->where('whiteboard.is_deleted', '0');
		});
    }
}
